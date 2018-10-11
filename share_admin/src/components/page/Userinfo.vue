<template>
    <div>
        <PageTitle title="个人信息"></PageTitle>
        <div class="pageWrapper">
            <div class="updateWrapper">
                <div class="title">请填写以下信息</div>
                <el-form :model="ruleForm" :rules="rules" ref="ruleForm" label-width="100px" class="demo-ruleForm" label-position="top" v-loading="loading">
                    <el-form-item label="用户名称" prop="name">
                        <el-input v-model="ruleForm.name" placeholder="请输入用户名称" disabled></el-input>
                    </el-form-item>
                    <el-form-item label="用户描述" prop="desc">
                        <el-input v-model="ruleForm.desc" placeholder="请输入用户描述" disabled></el-input>
                    </el-form-item>
                    <el-form-item label="用户邮箱" prop="email">
                        <el-input v-model="ruleForm.email" placeholder="请输入用户邮箱" disabled></el-input>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="submitForm('ruleForm')" disabled>保存</el-button>
                    </el-form-item>
                </el-form>
            </div>
        </div>
    </div>
</template>
<script>
    import Vue from 'vue'
    import PageTitle from '../frame/PageTitle.vue'
    import { mapState } from 'vuex'
    import {
        Input,
        Button,
        Form,
        FormItem,
        Message,
        Loading
    } from 'element-ui'
    import { checkToken }  from '../ajax';
    import { aesencode, aesdecode }  from '../utils';

    Vue.prototype.$message = Message;
    Vue.use(Input);
    Vue.use(Button);
    Vue.use(Form);
    Vue.use(FormItem);
    Vue.use(Loading);

    export default {
        computed: {
            ...mapState([
                'userInfo'
            ])
        },
        data(){
            return {
                loading: false,
                ruleForm: {
                    name: '',
                    desc: '',
                    email: ''
                },
                rules: {
                    name: [
                        { required: true, message: '用户名称', trigger: 'blur' }
                    ],
                    desc: [
                        { required: true, message: '用户描述', trigger: 'blur' }
                    ],
                    email: [
                        { required: true, message: '用户邮箱', trigger: 'blur' }
                    ],
                }
            }
        },
        methods:{
            submitForm(formName) {
                var that = this;
                this.$refs[formName].validate((valid) => {
                    if (valid) {
                        checkToken(function () {
                            that.loading = true;
                            that.axios.post('/admins/store', {
                                id: that.userInfo.id,
                                name: that.ruleForm.name,
                                desc: that.ruleForm.desc,
                                email: that.ruleForm.email,
                                privilege: encodeURIComponent(aesencode('update'))
                            })
                            .then(function (response) {
                                that.loading = false;
                                that.userInfo.name = that.ruleForm.name;
                                that.userInfo.desc = that.ruleForm.desc;
                                that.userInfo.email = that.ruleForm.email;
                                Message.success({
                                    message: '恭喜你，修改成功'
                                });

                                that.$store.dispatch('userInfo', {
                                    userInfo: that.userInfo
                                });
                            })
                            .catch(function (error) {
                                that.loading = false;
                                Message.warning({
                                    message: '修改失败'
                                });
                            });
                        });
                    } else {
                        return false;
                    }
                });
            },
            resetForm(formName) {
                this.$refs[formName].resetFields();
            },
        },
        components: {
            PageTitle
        },
        mounted(){
            this.ruleForm.name = this.userInfo.name;
            this.ruleForm.desc = this.userInfo.desc;
            this.ruleForm.email = this.userInfo.email;
        }
    }
</script>
<style>
    .child-dialog .el-pagination{
        border: none;
    }
</style>