<template>
    <div class="view-content">
        <el-container v-loading="outing">
            <el-header>
                <div class="headerLeft" :style="{width: collapse ? '45px' : '180px'}">
                    <img class="icon-home logo-icon" :class="collapse ? 'logo-collapse' : ''" :src="collapse ? require('../../assets/img/logo.png') : require('../../assets/img/title.png')"></img>
                    <span class="sys-name" v-if="!collapse && 1==2">{{sitename}}</span>
                </div>
                <div class="headerRight" :style="{width: collapse ? 'calc(100% - 45px)' : 'calc(100% - 180px)'}">
                    <i class="iconfont icon-sort menu-icon" @click="change"></i>
                    <span class="sys-name">管理系统</span>
                    <el-dropdown class="myinfo" trigger="click" @command="handleCommand">
                        <span class="el-dropdown-link">
                            {{userInfo.name}}<i class="el-icon-arrow-down el-icon--right"></i>
                        </span>
                        <el-dropdown-menu slot="dropdown">
                            <el-dropdown-item command="personal">个人信息</el-dropdown-item>
                            <el-dropdown-item command="password">修改密码</el-dropdown-item>
                            <el-dropdown-item divided command="logout">退出</el-dropdown-item>
                        </el-dropdown-menu>
                    </el-dropdown>
                </div>
                <div class="clear"></div>
            </el-header>
            <el-container>
                <el-aside :style="{width: collapse ? '64px' : '200px'}" :class="{inactive : collapse }">
                    <Lside></Lside>
                </el-aside>
                <el-container>
                    <Loading v-if="loading"></Loading>
                    <transition :name="transitionName">
                        <router-view class="child-view"></router-view>
                    </transition>
                </el-container>
            </el-container>
        </el-container>
    </div>
</template>

<script>
    import Vue from 'vue'
    import { mapGetters, mapState } from 'vuex'
    import {
        Container,
        Header,
        Aside,
        Main,
        Footer,
        Dropdown,
        DropdownMenu,
        DropdownItem,
        Loading
    } from 'element-ui'
    import Lside from './Lside.vue'

    Vue.use(Container);
    Vue.use(Header);
    Vue.use(Aside);
    Vue.use(Main);
    Vue.use(Footer);
    Vue.use(Dropdown);
    Vue.use(DropdownMenu);
    Vue.use(DropdownItem);
    Vue.use(Loading);

    export default {
        computed: {
            ...mapState([
                'collapse', 'sitename', 'userInfo'
            ]),
            ...mapGetters([
                'loading',
            ])
        },
        data () {
            return {
                transitionName: 'slide-left',
                outing: false,
            }
        },
        methods:{
            change(){
                this.$store.dispatch('collapse', {});
            },
            handleCommand(command) {
                if(command == 'personal') {
                    this.$store.dispatch('activeIndex', {
                        activeIndex: '/admin/info'
                    });

                    this.$router.push({
                        path: '/admin/info'
                    });
                }
                else if(command == 'password') {
                    this.$store.dispatch('activeIndex', {
                        activeIndex: '/admin/chgpwd'
                    });

                    this.$router.push({
                        path: '/admin/chgpwd'
                    });
                }
                else if(command == 'logout') {
                    this.logout();
                }
            },
            logout(){
                var that = this;
                that.outing = true;
                that.axios.get('/logout', {params : {}})
                .then(function (response) {
                    that.outing = false;
                    that.$store.dispatch('logout', {});
                    location.reload();
                })
                .catch(function (error) {
                    that.outing = false;
                });
            }
        },
        components:{
            Lside
        },
        watch: {
            '$route' (to, from) {
                let isBack = this.$router.isBack;
                if(typeof(to.meta.agent) !="undefined" && to.meta.agent == 'pc' && !isBack){
                    //pc页面非返回页面
                    this.transitionName = 'fadeup';
                }
                else if(typeof(from.meta.agent) !="undefined" && from.meta.agent == 'pc' && isBack){
                    //PC页面返回页面
                    this.transitionName = 'fadeup';
                }
                else {
                    //手机页面
                    if (isBack) {
                        this.transitionName = 'slide-right';
                    } else {
                        this.transitionName = 'slide-left';
                    }
                }

                this.$router.isBack = false;
            }
        },
    }
</script>

<style lang="scss">
    .view-content{
        width:100%;
        height:100%;
        overflow: hidden;
    }
    .router-link-active{
        color:#f60;
        font-size: 0.3rem;
    }
    .child-view {
        position: absolute;
        width:100%;
        height:100%;
        transition: all 250ms cubic-bezier(.55,0,.1,1);
        backface-visibility: hidden;
        z-index: 0;
        background-color: #f3f4fb;
    }
    aside.inactive .mtext{
        display: none;
    }
    .el-dropdown{
        line-height: 20px;
    }
    .myinfo{
        cursor: pointer;
    }
    .myinfo.el-dropdown{
        float: right;
        margin-top: 20px;
    }
    .el-dropdown-link{
        color: white;
    }
    .menu-icon{
        position: relative;
        top:3px;
        font-size: 30px;
        margin-right: 10px;
    }
    .logo-icon{
        font-size: 26px;
        position: relative;
        top:8px;
        color: #fff;
        margin-right: 10px;
    }
    .sys-name{
        font-size: 22px;
        color: #fff;
    }
    .slide-left-enter, .slide-right-leave-active {
        opacity: 0;
        -webkit-transform: translate(100%, 0);
        transform: translate(100%, 0);
    }
    .slide-left-leave-active, .slide-right-enter {
        opacity: 0;
        -webkit-transform: translate(-100%, 0);
        transform: translate(-100%, 0);
    }
    .fade-enter-active, .fade-leave-active {
        transition: opacity .5s ease;
    }
    .fade-enter, .fade-leave-active {
        opacity: 0
    }
    .zoom-enter-active {
        animation: zoomInLeft .5s;
    }
    .zoom-leave-active {
        animation: zoomOutRight .5s;
    }
    @keyframes zoomInLeft {
        from {
            opacity: 0;
            transform: scale3d(.1, .1, .1) translate3d(-1000px, 0, 0);
            animation-timing-function: cubic-bezier(0.550, 0.055, 0.675, 0.190);
        }
        60% {
            opacity: 1;
            transform: scale3d(.475, .475, .475) translate3d(10px, 0, 0);
            animation-timing-function: cubic-bezier(0.175, 0.885, 0.320, 1);
        }
    }
    @keyframes zoomOutRight {
        40% {
            opacity: 1;
            transform: scale3d(.475, .475, .475) translate3d(-42px, 0, 0);
        }
        to {
            opacity: 0;
            transform: scale(.1) translate3d(2000px, 0, 0);
            transform-origin: right center;
        }
    }
    .fadeup-enter-active {
        animation: fadeInUp .4s;
    }
    .fadeup-leave-active {
        animation: fadeInUp .4s reverse;
    }
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    @keyframes fadeOutUp {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translate3d(0, -30px, 0);
        }
    }

    .el-container{
        position: relative;
    }
    .el-header, .el-footer {
        background-color: #B3C0D1;
        color: #333;
        text-align: center;
    }
    .el-header {
        line-height: 60px;
        background-color: #16aad8;
        z-index: 99;
    }
    .el-footer {
        line-height: 60px;
        background-color: #edf1f2;
    }
    .el-aside {
        color: #333;
        text-align: left;
        overflow: visible;
        transition: width 0.1s;
    }
    .el-main {
        background-color: #E9EEF3;
        color: #333;
        text-align: center;
    }
    .view-content > .el-container {
        height: 100%;
    }
    .headerLeft{
        width: 210px;
        height: 100%;
        float: left;
        text-align: left;
    }
    .headerRight{
        height: 100%;
        width: calc( 100% - 210px );
        float: left;
        text-align: left;
        padding-left: 10px;
        box-sizing: border-box;
        font-size: 24px;
        color: #fff;
    }
    .pageWrapper{
        width: 100%;
        height: calc( 100% - 80px );
        padding: 20px;
        box-sizing: border-box;
        overflow: auto;
    }
    .searchWrapper{
        width: 100%;
        padding-bottom: 20px;
        box-sizing: border-box;
    }
    .addBtn{
        float: right;
    }
    .searchWrapper .el-input-group{
        width:250px;
    }
    .el-pagination{
        background-color: white;
        display: flex;
        justify-content: flex-end;
        padding: 30px 20px;
        border: 1px solid #ebeef5;
        border-top:  none;
    }
    .clear{
        clear: both;
    }
    aside.inactive:hover .mtext{
        display: block;
    }
    .icon-home{
        height: 45px;
        transition: all 0.3s;
        position: relative;
        left: 3px;
    }
    .icon-home.logo-collapse{
        left: -6px;
    }
</style>
