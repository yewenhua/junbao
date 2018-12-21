<template>
    <div>
        <PageTitle title="我的客户"></PageTitle>
        <div class="pageWrapper">
            <div class="lookWrapper" v-if="!edit">
                <div class="searchWrapper">
                    <el-input placeholder="请输入内容" v-model="searchkey" size="small">
                        <el-button slot="append" size="small" icon="el-icon-search" @click="search">搜索</el-button>
                    </el-input>
                    <span style="margin-left: 5px;">总营业额：{{totalMoney}}元，总设备数：{{total_devices_num}}，总激活数：{{total_active_device_num}}，总激活率：{{total_active_device_rate}}%，24H激活率：{{total_recent_active_rate}}%，24H使用率：{{total_recent_use_rate}}%</span>
                    <el-button type="primary" size="small" class="addBtn" icon="el-icon-plus" @click="addUser" v-if="hasPermission('admin/myagents', 'add')">添加用户</el-button>
                </div>
                <el-table
                        :data="tableData"
                        empty-text="暂无数据"
                        v-loading="loading"
                        stripe
                        border
                        cell-class-name="txt-center"
                        header-cell-class-name="txt-center"
                        @selection-change="handleSelectionChange"
                        style="width: 100%">
                    <el-table-column
                            type="selection"
                            width="40">
                    </el-table-column>
                    <el-table-column
                            type="index"
                            width="40"
                            :index="indexMethod">
                    </el-table-column>
                    <el-table-column
                            prop="role_name"
                            label="角色"
                            width="70">
                        <template slot-scope="scope">
                            <el-button type="text" size="small">{{scope.row.role_name ? scope.row.role_name : ''}}</el-button>
                        </template>
                    </el-table-column>
                    <el-table-column
                            prop="name"
                            width="100"
                            class-name="wrap-hundred"
                            label="名称">
                    </el-table-column>
                    <el-table-column
                            prop="contact"
                            width="100"
                            class-name="wrap-hundred"
                            label="联系人">
                    </el-table-column>
                    <el-table-column
                            prop="phone"
                            label="联系电话">
                    </el-table-column>
                    <el-table-column
                            prop="desc"
                            class-name="description"
                            label="联系地址">
                        <template slot-scope="scope">
                            <div :title="address(scope.$index, scope.row)">{{address(scope.$index, scope.row)}}</div>
                        </template>
                    </el-table-column>
                    <el-table-column
                            prop="created_at"
                            width="100"
                            label="投放时间">
                        <template slot-scope="scope">
                            <div>{{scope.row.created_at.substring(0,10)}}</div>
                        </template>
                    </el-table-column>
                    <el-table-column
                            prop="device_num"
                            width="65"
                            label="设备数">
                    </el-table-column>
                    <el-table-column
                            prop="active_num"
                            width="65"
                            label="激活数">
                    </el-table-column>
                    <el-table-column
                            prop="active_device_rate"
                            width="65"
                            label="激活率">
                        <template slot-scope="scope">
                            <div>{{scope.row.active_device_rate + '%'}}</div>
                        </template>
                    </el-table-column>
                    <el-table-column
                            prop="recent_active_rate"
                            width="65"
                            label="近一天激活率">
                        <template slot-scope="scope">
                            <div>{{scope.row.recent_active_rate + '%'}}</div>
                        </template>
                    </el-table-column>
                    <el-table-column
                            prop="recent_use_rate"
                            width="65"
                            label="近一天使用率">
                        <template slot-scope="scope">
                            <div>{{scope.row.recent_use_rate + '%'}}</div>
                        </template>
                    </el-table-column>
                    <el-table-column
                            prop="operate_money"
                            label="营业额（元）">
                    </el-table-column>
                    <el-table-column
                            prop="unclear_money"
                            label="未结算（元）">
                        <template slot-scope="scope">
                            <div>{{scope.row.unclear_money + scope.row.freeze_money}}</div>
                        </template>
                    </el-table-column>
                    <el-table-column
                            prop="clear_money"
                            label="已结算（元）">
                    </el-table-column>
                    <el-table-column label="操作" width="75" v-if="hasPermission('admin/myagents', 'update') || hasPermission('admin/myagents', 'delete')">
                        <template slot-scope="scope">
                            <el-button
                                    size="mini"
                                    type="primary"
                                    :disabled="scope.row.id == 2"
                                    v-if="hasPermission('admin/myagents', 'update')"
                                    @click="handleEdit(scope.$index, scope.row)">编辑</el-button>
                        </template>
                    </el-table-column>
                </el-table>
                <el-pagination
                        @size-change="handleSizeChange"
                        @current-change="handleCurrentChange"
                        :current-page.sync="currentPage"
                        :page-size="perPage"
                        layout="total, prev, pager, next"
                        :total="total">
                </el-pagination>
                <div class="batch_delete" v-if="hasPermission('admin/myagents', 'delete')">
                    <el-button type="danger" :disabled="multipleSelection.length <= 0" @click="batchDelete">批量删除</el-button>
                    <el-button type="warning" :disabled="multipleSelection.length <= 0" @click="resetPwd">重置密码</el-button>
                </div>
            </div>

            <div class="updateWrapper" v-if="edit">
                <div class="title">请填写以下信息</div>
                <el-form :model="ruleForm" :rules="rules" ref="ruleForm" label-width="100px" class="demo-ruleForm" label-position="top" v-loading="loading">
                    <div class="form-item">
                        <el-form-item label="用户类型" prop="type">
                            <el-radio border v-model="ruleForm.type" size="medium" label="operate" :disabled="activeUserId ? true : false">运营</el-radio>
                            <el-radio border v-model="ruleForm.type" size="medium" label="manitenance" :disabled="activeUserId ? true : false">市场维护</el-radio>
                            <el-radio border v-model="ruleForm.type" size="medium" label="agent" :disabled="activeUserId ? true : false">商家</el-radio>
                        </el-form-item>
                    </div>
                    <div class="form-item">
                        <div class="left">
                            <el-form-item label="用户名称" prop="name">
                                <el-input v-model="ruleForm.name" placeholder="请输入用户名称"></el-input>
                            </el-form-item>
                        </div>
                        <div class="right">
                            <el-form-item label="联系人" prop="contact">
                                <el-input v-model="ruleForm.contact" placeholder="请输入联系人"></el-input>
                            </el-form-item>
                        </div>
                        <div class="clear"></div>
                    </div>
                    <div class="form-item">
                        <div class="left">
                            <el-form-item label="客服联系电话：(对外)" prop="phone">
                                <el-input v-model="ruleForm.phone" placeholder="请输入客服联系电话"></el-input>
                            </el-form-item>
                        </div>
                        <div class="right">
                            <el-form-item label="用户备注" prop="desc">
                                <el-input v-model="ruleForm.desc" placeholder="请输入用户备注"></el-input>
                            </el-form-item>
                        </div>
                        <div class="clear"></div>
                    </div>
                    <div class="form-item">
                        <div class="left">
                            <el-form-item label="选择地区" prop="area">
                                <CitySelect ref="city" :value="ruleForm.area"></CitySelect>
                            </el-form-item>
                        </div>
                        <div class="right">
                            <el-form-item label="详细地址" prop="address">
                                <el-input v-model="ruleForm.address" placeholder="请输入详细地址"></el-input>
                            </el-form-item>
                        </div>
                        <div class="clear"></div>
                    </div>
                    <div class="form-item">
                        <div class="left">
                            <el-form-item label="电子邮箱" prop="email">
                                <el-input v-model="ruleForm.email" placeholder="请输入电子邮箱"></el-input>
                            </el-form-item>
                        </div>
                        <div class="right">
                            <el-form-item label="状态是否可用" prop="isopen">
                                <el-switch
                                        v-model="ruleForm.isopen"
                                        active-color="#409eff"
                                        inactive-color="#dcdfe6">
                                </el-switch>
                            </el-form-item>
                        </div>
                        <div class="clear"></div>
                    </div>
                    <el-form-item>
                        <el-button type="primary" @click="submitForm('ruleForm')">提交</el-button>
                        <el-button type="danger" @click="resetForm('ruleForm')">重置</el-button>
                        <el-button type="warning" @click="cancelForm()">取消</el-button>
                    </el-form-item>
                </el-form>
            </div>
        </div>
    </div>
</template>
<script>
    import Vue from 'vue'
    import {
        Table,
        TableColumn,
        Pagination,
        Input,
        Button,
        Form,
        FormItem,
        Checkbox,
        CheckboxGroup,
        Message,
        Loading,
        MessageBox,
        Switch,
        Radio
    } from 'element-ui'
    import PageTitle from '../frame/PageTitle.vue'
    import { checkToken }  from '../ajax';
    import { aesencode, aesdecode }  from '../utils';
    import CitySelect from '../CitySelect/Index.vue'

    Vue.use(Table);
    Vue.use(TableColumn);
    Vue.use(Pagination);
    Vue.use(Input);
    Vue.use(Button);
    Vue.use(Form);
    Vue.use(FormItem);
    Vue.use(Checkbox);
    Vue.use(CheckboxGroup);
    Vue.use(Loading);
    Vue.use(Switch);
    Vue.use(Radio);
    Vue.prototype.$confirm = MessageBox.confirm;
    Vue.prototype.$prompt = MessageBox.prompt;
    Vue.prototype.$message = Message;

    export default {
        data(){
            return {
                currentPage: 1,
                perPage: 10,
                total: 1,
                searchkey: '',
                loading: false,
                totalMoney: 0,
                total_devices_num: 0,
                total_active_device_num: 0,
                total_active_device_rate: 0,
                total_recent_active_rate: 0,
                total_recent_use_rate: 0,
                edit: false,
                activeUserId: '',
                activeUserName: '',
                tableData: [],
                multipleSelection: [],
                ruleForm: {
                    type: 'operate',
                    name: '',
                    desc: '',
                    contact: '',
                    phone: '',
                    area: [],
                    address: '',
                    isopen: true,
                    email: ''
                },
                rules: {
                    type: [
                        { required: true, message: '用户类型', trigger: 'blur' },
                    ],
                    name: [
                        { required: true, message: '用户名称', trigger: 'blur' },
                    ],
                    contact: [
                        { required: true, message: '联系人', trigger: 'blur' },
                    ],
                    area: [
                        { required: true, message: '省市地区', trigger: 'blur' },
                    ],
                    phone: [
                        { required: true, message: '联系电话', trigger: 'blur' },
                    ],
                    address: [
                        { required: true, message: '详细地址', trigger: 'blur' },
                    ]
                }
            }
        },
        methods:{
            handleSizeChange(val) {

            },
            handleCurrentChange(val) {
                this.currentPage = val;
                this.users();
            },
            addUser(){
                this.edit = true;
                this.activeUserId = '';
                this.activeUserName = '';
                this.ruleForm.name = '';
                this.ruleForm.desc = '';
                this.ruleForm.contact = '';
                this.ruleForm.phone = '';
                this.ruleForm.area = [];
                this.ruleForm.address = '';
                this.ruleForm.isopen = true;
            },
            submitForm(formName) {
                let that = this;
                let val = that.$refs.city.getVal();
                if(!val || val.length == 0){
                    Message.warning({
                        message: '请选择地区'
                    });
                    return false;
                }

                that.ruleForm.area = val;
                that.$refs[formName].validate((valid) => {
                    if (valid) {
                        checkToken(function () {
                            that.loading = true;
                            that.axios.post('/admins/store', {
                                id: that.activeUserId,
                                name: that.ruleForm.name,
                                desc: that.ruleForm.desc,
                                email: that.ruleForm.email,
                                contact: that.ruleForm.contact,
                                phone: that.ruleForm.phone,
                                area: JSON.stringify(that.ruleForm.area),
                                address: that.ruleForm.address,
                                isopen: that.ruleForm.isopen ? 1 : 0,
                                type: that.ruleForm.type,
                                privilege: that.activeUserId ? encodeURIComponent(aesencode('update')) : encodeURIComponent(aesencode('add'))
                            })
                            .then(function (response) {
                                that.loading = false;
                                if (response.data.code == 0) {
                                    Message.success({
                                        message: '操作成功'
                                    });

                                    that.ruleForm.name = '';
                                    that.ruleForm.desc = '';
                                    that.ruleForm.email = '';
                                    that.cancelForm();
                                    that.users();
                                }
                                else{
                                    Message.warning({
                                        message: response.data.message
                                    });
                                }
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
            cancelForm(){
                this.edit = false;
            },
            users(){
                var that = this;
                checkToken(function () {
                    that.loading = true;
                    that.axios.get('/admins/children', {
                        params : {
                            page : that.currentPage,
                            num: that.perPage,
                            search: that.searchkey,
                            privilege: encodeURIComponent(aesencode('read'))   //js加密后的字符串里面的+被浏览器解析成了空格，php解密的时候会出错，encodeURIComponent解决
                        }
                    })
                    .then(function (response) {
                        if(response.data.code == 0){
                            that.tableData = response.data.data.data;
                            that.total = response.data.data.total;
                            that.totalMoney = response.data.data.all_money;
                            that.total_devices_num = response.data.data.total_devices_num;
                            that.total_active_device_num = response.data.data.total_active_device_num;
                            that.total_active_device_rate = response.data.data.total_active_device_rate;
                            that.total_recent_active_rate = response.data.data.total_recent_active_rate;
                            that.total_recent_use_rate = response.data.data.total_recent_use_rate;
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
            indexMethod(index){
                return index + 1;
            },
            search(){
                this.currentPage = 1;
                this.total = 1;
                this.tableData = [];
                this.users()
            },
            handleSelectionChange(val){
                this.multipleSelection = val;
            },
            handleEdit(index, row) {
                this.activeUserId = row.id;
                this.activeUserName = row.name;
                this.edit = true;
                this.ruleForm.type = row.type;
                this.ruleForm.name = row.name;
                this.ruleForm.desc = row.desc;
                this.ruleForm.email = row.email;
                this.ruleForm.contact = row.contact;
                this.ruleForm.phone = row.phone;
                this.ruleForm.area = row.area ? JSON.parse(row.area) : [];
                this.ruleForm.address = row.address;
                this.ruleForm.isopen = row.isopen == 1 ? true : false;
            },
            batchDelete(){
                var that = this;
                if(that.multipleSelection.length <= 0){
                    Message.warning({
                        message: '请选择要删除的选项'
                    });
                    return false;
                }

                that.$prompt('请输入确认密码?', '提示', {
                    confirmButtonText: '确定',
                    cancelButtonText: '取消'
                }).then(({ value }) => {
                    checkToken(function () {
                        that.loading = true;
                        let idstring = '';
                        for(let i=0; i<that.multipleSelection.length; i++){
                            if(that.multipleSelection[i].id == 2){
                                continue;
                            }

                            if(idstring){
                                idstring += ',' + that.multipleSelection[i].id;
                            }
                            else{
                                idstring = that.multipleSelection[i].id;
                            }
                        }

                        that.axios.post('/admins/childdelete', {
                            idstring: idstring,
                            password: encodeURIComponent(aesencode(value)),
                            privilege: encodeURIComponent(aesencode('delete'))
                        })
                        .then(function (response) {
                            that.loading = false;
                            if (response.status = 200 && response.data && response.data.code == 0) {
                                Message.success({
                                    message: '删除成功'
                                });

                                that.currentPage = 1;
                                that.total = 1;
                                that.tableData = [];
                                that.users();
                            }
                            else{
                                Message.error({
                                    message: response.data.message
                                });
                            }
                        })
                        .catch(function (error) {
                            that.loading = false;
                            Message.warning({
                                message: '删除失败'
                            });
                        });
                    });
                }).catch((error) => {

                });
            },
            address(index, row){
                let address = '';
                if(row.area){
                    let tmp = JSON.parse(row.area);
                    if(tmp.length == 1){
                        address = tmp[0] + row.address;
                    }
                    else if(tmp.length == 2){
                        address = tmp[0] + tmp[1] + row.address;
                    }
                    else if(tmp.length == 3){
                        address = tmp[0] + tmp[1] + tmp[2] + row.address;
                    }
                }
                return address;
            },
            resetPwd(index, row){
                var that = this;
                if(that.multipleSelection.length <= 0){
                    Message.warning({
                        message: '请选择要重置的选项'
                    });
                    return false;
                }

                that.$confirm('此操作将重置密码, 是否继续?', '提示', {
                    confirmButtonText: '确定',
                    cancelButtonText: '取消',
                    type: 'warning'
                }).then(() => {
                    checkToken(function () {
                        that.loading = true;
                        let idstring = '';
                        for(let i=0; i<that.multipleSelection.length; i++){
                            if(idstring){
                                idstring += ',' + that.multipleSelection[i].id;
                            }
                            else{
                                idstring = that.multipleSelection[i].id;
                            }
                        }
                        that.axios.post('/admins/resetpwd', {
                            idstring: idstring,
                            privilege: encodeURIComponent(aesencode('delete'))
                        })
                        .then(function (response) {
                            that.loading = false;
                            Message.success({
                                message: '恭喜你，重置成功'
                            });
                        })
                        .catch(function (error) {
                            that.loading = false;
                            Message.warning({
                                message: '重置失败'
                            });
                        });
                    });
                }).catch((error) => {

                });
            }
        },
        components: {
            PageTitle,
            CitySelect
        },
        mounted() {
            this.users();
        }
    }
</script>
<style scoped  lang="scss">
    @import '../../assets/scss/base/mixins';
    @import '../../assets/scss/base/placeholder';
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
    .userRoleWrapper{
        padding-top: 12px;
    }
    .roleItem{
        margin-right: 25px;
        margin-bottom: 15px;
        display: inline-block;
    }
    .privilegeItem .name{
        text-align: center;
        font-weight: 700;
    }
    .el-checkbox+.el-checkbox{
        margin-left: 20px;
    }
    .form-item .left{
        @extend %left;
        width: 50%;
        box-sizing: border-box;
        padding-right: 20px;
    }
    .form-item .right{
        @extend %left;
        width: 50%;
        box-sizing: border-box;
        padding-left: 20px;
    }
    .status.active{
        color: green;
    }
    .status.unactive{
        color: red;
    }
    .status.wait{
        color: #e6a23c;
    }
    .status.agree{
        color: green;
    }
    .status.disagree{
        color: red;
    }
</style>