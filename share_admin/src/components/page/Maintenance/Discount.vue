<template>
    <div>
        <PageTitle title="分成比列"></PageTitle>
        <div class="pageWrapper">
            <div class="updateWrapper">
                <div class="title">请填写以下信息</div>
                <el-form :model="ruleForm" :rules="rules" ref="ruleForm" label-width="100px" class="demo-ruleForm" label-position="top" v-loading="loading">
                    <el-form-item label="代理商" prop="uid">
                        <el-select v-model="searchUid" placeholder="代理商" @change="chgUid" size="medium" style="width: 100%;">
                            <el-option key="全部用户" label="全部用户" value="all" v-if="usertype=='admin'"></el-option>
                            <el-option
                                    v-for="item in agents"
                                    :key="item.id"
                                    :label="item.name"
                                    :value="item.id">
                            </el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="分成比列（%）" prop="value">
                        <el-input v-model="ruleForm.value" placeholder="请输入分成比列"></el-input>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="submitForm('ruleForm')" v-if="usertype=='admin'">提交</el-button>
                        <el-button type="danger" @click="resetForm('ruleForm')" v-if="usertype=='admin'">重置</el-button>
                    </el-form-item>
                </el-form>
            </div>
        </div>
    </div>
</template>
<script>
    import Vue from 'vue'
    import { mapState } from 'vuex'
    import {
        Input,
        Button,
        Form,
        FormItem,
        Message,
        Loading,
        MessageBox,
        Select,
        Option
    } from 'element-ui'
    import PageTitle from '../../frame/PageTitle.vue'
    import { checkToken }  from '../../ajax';
    import { aesencode, aesdecode }  from '../../utils';

    Vue.use(Input);
    Vue.use(Button);
    Vue.use(Form);
    Vue.use(FormItem);
    Vue.use(Loading);
    Vue.use(Select);
    Vue.use(Option);
    Vue.prototype.$confirm = MessageBox.confirm;
    Vue.prototype.$message = Message;

    export default {
        computed: {
            ...mapState(["domainUrl", 'baseURL']),
        },
        data(){
            return {
                activeId: '',
                loading: false,
                searchUid: 'all',
                agents: [],
                usertype: 'agent',
                ruleForm: {
                    value: ''
                },
                rules: {
                    value: [
                        { required: true, message: '请输入返利点', trigger: 'blur' }
                    ]
                }
            }
        },
        methods:{
            getData(){
                var that = this;
                checkToken(function () {
                    that.loading = true;
                    that.axios.get('/setting/discount', {
                        params: { //请求参数
                            uid: that.searchUid,
                            privilege: encodeURIComponent(aesencode('read'))
                        }
                    })
                    .then(function (response) {
                        if (response.data.code == 0) {
                            that.activeId = response.data.data.id;
                            that.ruleForm.value = response.data.data.value;
                        }
                        else{
                            that.activeId = '';
                            that.ruleForm.value = '';
                        }
                        that.loading = false;
                    })
                    .catch(function (error) {
                        Message.error({
                            message: '未知错误'
                        });
                        that.loading = false;
                    });
                });
            },
            submitForm(formName) {
                var that = this;
                this.$refs[formName].validate((valid) => {
                    if (valid) {
                        checkToken(function () {
                            that.loading = true;
                            that.axios.post('/setting/discount', {
                                id: that.activeId,
                                uid: that.searchUid,
                                value: that.ruleForm.value,
                                privilege: that.activeId ? encodeURIComponent(aesencode('update')) : encodeURIComponent(aesencode('add'))
                            })
                            .then(function (response) {
                                that.loading = false;
                                Message.success({
                                    message: '提交成功'
                                });
                            })
                            .catch(function (error) {
                                that.loading = false;
                                Message.warning({
                                    message: '未知错误'
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
            chgUid(){
                this.getData();
            },
            getAgents(){
                var that = this;
                checkToken(function () {
                    that.loading = true;
                    that.axios.get('/admins/lists', {
                        params: { //请求参数
                            privilege: encodeURIComponent(aesencode('read'))
                        }
                    })
                    .then(function (response) {
                        if (response.data.code == 0) {
                            that.agents = response.data.data.list;
                            that.usertype = response.data.data.type;
                            if(that.usertype == 'agent'){
                                that.agents[0].name = '我运营的设备';
                                that.searchUid = that.agents[0].id;
                            }
                            that.getData();
                        }
                        that.loading = false;
                    })
                    .catch(function (error) {
                        Message.error({
                            message: '未知错误'
                        });
                        that.loading = false;
                    });
                });
            }
        },
        components: {
            PageTitle
        },
        mounted() {
            this.getAgents();
        }
    }
</script>
<style>
    .updateWrapper{
        background-color: white;
        border: 1px solid #ebeef5;
        padding: 20px;
    }
    .updateWrapper .title{
        background-color: #f6f8f8;
        font-size: 16px;
        margin: -20px -20px 10px -20px;
        padding: 15px 20px;
        color: #333;
        font-weight: bold;
    }
    .updateWrapper .el-form--label-top .el-form-item__label{
        padding: 0px;
    }
    .el-form-item__content .edui-toolbar{
        line-height: normal;
    }
</style>