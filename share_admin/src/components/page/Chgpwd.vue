<template>
    <div>
        <PageTitle title="修改密码"></PageTitle>
        <div class="pageWrapper">
            <div class="updateWrapper">
                <div class="title">请填写以下信息</div>
                <el-form :model="ruleForm" :rules="rules" ref="ruleForm" label-width="100px" class="demo-ruleForm" label-position="top" v-loading="loading">
                    <el-form-item label="原密码" prop="oldpwd">
                        <el-input v-model="ruleForm.oldpwd" placeholder="请输入原密码"></el-input>
                    </el-form-item>
                    <el-form-item label="新密码" prop="newpwd">
                        <el-input v-model="ruleForm.newpwd" placeholder="请输入新密码"></el-input>
                    </el-form-item>
                    <el-form-item label="新密码确认" prop="renewpwd">
                        <el-input v-model="ruleForm.renewpwd" placeholder="请再次输入新密码"></el-input>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="submitForm('ruleForm')">保存</el-button>
                        <el-button type="danger" @click="resetForm('ruleForm')">重置</el-button>
                    </el-form-item>
                </el-form>
            </div>
        </div>
    </div>
</template>
<script>
    import Vue from 'vue'
    import PageTitle from '../frame/PageTitle.vue'
    import {
        Input,
        Button,
        Form,
        FormItem,
        Message,
        Loading
    } from 'element-ui'
    import { mapState } from 'vuex'
    import { checkToken }  from '../ajax';
    import { aesencode, aesdecode }  from '../utils';

    Vue.prototype.$message = Message;

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
                    oldpwd: '',
                    newpwd: '',
                    renewpwd: ''
                },
                rules: {
                    oldpwd: [
                        { required: true, message: '原密码', trigger: 'blur' }
                    ],
                    newpwd: [
                        { required: true, message: '新密码', trigger: 'blur' }
                    ],
                    renewpwd: [
                        { required: true, message: '新密码确认', trigger: 'blur' }
                    ],
                }
            }
        },
        methods:{
            submitForm(formName) {
                var that = this;
                that.$refs[formName].validate((valid) => {
                    if (valid) {
                        if(that.ruleForm.newpwd == that.ruleForm.renewpwd) {
                            checkToken(function () {
                                that.loading = true;
                                that.axios.post('/admins/chgpwd', {
                                    id: that.userInfo.id,
                                    oldpwd: that.ruleForm.oldpwd,
                                    newpwd: that.ruleForm.newpwd,
                                    privilege: encodeURIComponent(aesencode('update'))
                                })
                                .then(function (response) {
                                    that.loading = false;
                                    if (response.status = 200 && response.data && response.data.code == 0) {
                                        Message.success({
                                            message: '恭喜你，修改成功'
                                        });
                                    }
                                    else {
                                        Message.warning({
                                            message: response.data.message
                                        });
                                    }
                                })
                                .catch(function (error) {
                                    that.loading = false;
                                    Message.warning({
                                        message: '修改失败'
                                    });
                                });
                            });
                        }
                        else{
                            Message.warning({
                                message: '两次输入新密码不一致'
                            });
                        }
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
        }
    }
</script>
<style>

</style>