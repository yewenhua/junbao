<template>
    <div id="login">
        <div class="siginin_login_outer"></div>
        <div class="siginin_login_bg">
            <div class="siginin_title_outer"></div>
            <div class="siginin_title_bg">
                <img src="../../assets/img/logo.png" style="border-radius: 50%;">
                <span class="siginin_title ng-binding">{{sitename}}运营商管理系统</span>
            </div>
            <form name="form">
                <input type="text" placeholder="请输入手机号码" class="siginin_name ng-pristine ng-untouched ng-invalid ng-invalid-required" v-model="email" required="">
                <i class="iconfont icon-people glyphicon-user"></i>
                <input type="password" placeholder="请输入密码" class="siginin_pwd ng-pristine ng-untouched ng-invalid ng-invalid-required" v-model="password" required="">
                <i class="iconfont icon-lock glyphicon-lock"></i>
                <el-button type="primary" class="siginin_btn" :loading="loading" @click="login">登  录</el-button>
            </form>
        </div>
    </div>
</template>
<script>
    import Vue from 'vue'
    import { mapState } from 'vuex'
    import {
        Input,
        Message,
        Button
    } from 'element-ui'

    Vue.use(Input);
    Vue.use(Button);

    export default {
        computed: {
            ...mapState([
                'token', 'sitename'
            ])
        },
        data(){
            return {
                email: '',
                password: '',
                loading: false
            }
        },
        methods:{
            login(){
                if(!this.email){
                    Message.warning({
                        message: '用户名不能为空'
                    });
                    return false;
                }

                if(!this.password){
                    Message.warning({
                        message: '密码不能为空'
                    });
                    return false;
                }

                var that = this;
                that.loading = true;
                that.axios.post('/login', {
                    email: that.email,
                    password: that.password
                })
                .then(function (response) {
                    that.loading = false;
                    var welcome_flag = false;
                    var redirect_flag = false;
                    var roles = response.data.user.roles;
                    var permissions = [];
                    for(var i=0; i<roles.length; i++){
                        for(var j=0; j<roles[i].permissions.length; j++){
                            permissions.push(roles[i].permissions[j]);
                        }
                    }
                    //console.log(permissions);

                    var redirect = decodeURIComponent(that.$route.query.redirect || '/');
                    for(var i=0; i<permissions.length; i++){
                        var url = '/' + permissions[i].desc;
                        if(url == redirect){
                            redirect_flag = true;
                            break;
                        }

                        if(url == '/admin/user'){
                            welcome_flag = true;
                        }
                    }

                    var goto_url = '';
                    if(redirect_flag){
                        goto_url = redirect;
                    }
                    else if(welcome_flag){
                        goto_url = '/admin/user';
                    }
                    else{
                        goto_url = '/' + permissions[0].desc;
                    }

                    that.$store.dispatch('login', {
                        token: response.data.token,
                        permissions: permissions,
                        activeIndex: goto_url,
                        userInfo: response.data.user,
                        typeOptions: response.data.ptype,
                        brandOptions: response.data.pbrand
                    });

                    that.$router.push({
                        path: goto_url
                    })
                })
                .catch(function (error) {
                    that.loading = false;
                    if(error && error.error == 'invalid_credentials'){
                        Message.warning({
                            message: '您没有权限登录'
                        });
                        return false;
                    }
                });
            }
        }
    }
</script>
<style scoped>
    #login{
        height: 100%;
        width: 100%;
        background: url(../../assets/img/login_bg.jpg) no-repeat;
        background-size: 100% 100%;
        background-color: #fff;
    }
    .siginin_login_outer {
        background: #000;
        opacity: .2;
        filter: alpha(opacity=.2);
    }
    .siginin_login_bg, .siginin_login_outer {
        width: 430px;
        height: 320px;
        left: 50%;
        top: 50%;
        margin-top: -160px;
        margin-left: -215px;
        position: absolute;
        border: 1px solid #fff;
    }
    .siginin_title_outer {
        background: #000;
        opacity: .5;
        filter: alpha(opacity=.5);
    }
    .siginin_title_bg, .siginin_title_outer {
        height: 60px;
        line-height: 60px;
        width: 100%;
    }
    .siginin_title {
        color: white;
        font-size: 24px;
        margin-left: 12px;
        position: absolute;
        top: 0px;
        left:80px;
    }
    .siginin_login_bg form {
        position: relative;
    }
    input.siginin_name {
        margin-top: 45px;
        border: 1px solid #fff;
        border-radius: 3px;
    }
    .siginin_btn, input.siginin_name, input.siginin_pwd {
        margin-left: 55px;
        width: 320px;
        height: 39px;
        color: #fff;
    }
    input.siginin_name, input.siginin_pwd {
        display: block;
        margin-bottom: 20px;
        padding-left: 32px;
        background: 0 0;
        box-sizing: border-box;
        color: #fff;
    }
    .siginin_btn {
        line-height: 39px;
        text-align: center;
        font-size: 18px;
        border-radius: 3px;
    }
    input:focus:invalid, input[required]:invalid {
        box-shadow: none;
    }
    input.siginin_pwd {
        border: 1px solid #fff;
        border-radius: 3px;
    }
    .siginin_title_bg {
        position: absolute;
        left: 0;
        top: 0;
        padding-left: 25px;
        font-size: 20px;
        color: #fff;
        box-sizing: border-box;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .siginin_title_bg img {
        height: 45px;
        position: absolute;
        left:25px;
    }
    .siginin_login_bg input+i.glyphicon-user {
        position: absolute;
        left: 66px;
        top: 12px;
        color: #fff;
    }
    .siginin_login_bg input+i.glyphicon-lock {
        position: absolute;
        left: 66px;
        top: 71px;
        color: #fff;
    }
    .siginin_login_bg :-moz-placeholder { /* Mozilla Firefox 4 to 18 */
        color: #fff;
    }
    .siginin_login_bg ::-moz-placeholder { /* Mozilla Firefox 19+ */
        color: #fff;
    }
    .siginin_login_bg input:-ms-input-placeholder{
        color: #fff;
    }
    .siginin_login_bg input::-webkit-input-placeholder {
        color: #fff;
    }
    .el-button{
        padding: 0px;
    }
</style>