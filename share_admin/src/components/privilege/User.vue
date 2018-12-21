<template>
    <div>
        <PageTitle title="用户管理"></PageTitle>
        <div class="pageWrapper">
            <div class="lookWrapper" v-if="!edit && !editRole">
                <div class="searchWrapper">
                    <el-select v-model="searchRoleId" placeholder="角色" @change="chgRoleId" size="medium" style="width: 120px;">
                        <el-option key="全部角色" label="全部角色" value="all"></el-option>
                        <el-option
                                v-for="item in roleOptions"
                                :key="item.id"
                                :label="item.name"
                                :value="item.id">
                        </el-option>
                    </el-select>
                    <el-input placeholder="请输入内容" v-model="searchkey" size="medium">
                        <el-button slot="append" size="medium" icon="el-icon-search" @click="search">搜索</el-button>
                    </el-input>
                    <el-button type="primary" size="medium" class="addBtn" icon="el-icon-plus" @click="addUser" v-if="hasPermission('admin/user', 'add')">添加</el-button>
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
                            :selectable="selectable"
                            width="40">
                    </el-table-column>
                    <el-table-column
                            type="index"
                            width="40"
                            :index="indexMethod">
                    </el-table-column>
                    <el-table-column
                            fixed
                            prop="role_name"
                            label="角色"
                            v-if="hasPermission('admin/user', 'add') && hasPermission('admin/user', 'delete') && hasPermission('admin/user', 'update') && hasPermission('admin/user', 'read')"
                            width="70">
                        <template slot-scope="scope">
                            <el-button :disabled="scope.row.id == 2 || scope.row.level != 2" @click="handleClick(scope.$index, scope.row)" type="text" size="small">{{scope.row.role_name ? scope.row.role_name : '分配角色'}}</el-button>
                        </template>
                    </el-table-column>
                    <el-table-column
                            fixed
                            prop="name"
                            width="100"
                            class-name="wrap-hundred"
                            label="名称">
                        <template slot-scope="scope">
                            <div :title="scope.row.name" class="inner-cell">{{scope.row.name}}</div>
                        </template>
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
                            prop="maintenance"
                            label="维护人员">
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
                        <template slot-scope="scope">
                            <div>{{scope.row.operate_money}}</div>
                        </template>
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
                    <el-table-column
                            prop="discount"
                            label="分成比列">
                        <template slot-scope="scope">
                            <div>{{scope.row.discount + '%'}}</div>
                        </template>
                    </el-table-column>
                    <el-table-column
                            v-if="1==2"
                            prop="isopen"
                            width="80"
                            label="是否开启">
                        <template slot-scope="scope">
                            <div class="status" :class="scope.row.isopen == 1 ? 'active' : 'unactive'">{{scope.row.isopen == 1 ? '启用中' : '停用中'}}</div>
                        </template>
                    </el-table-column>
                    <el-table-column
                            prop="status"
                            width="160"
                            label="审核状态">
                        <template slot-scope="scope">
                            <div class="status" v-if="scope.row.status != 0" :class="scope.row.status == 1 ? 'agree' : scope.row.status == 2 ? 'disagree' : 'wait'">{{scope.row.status == 1 ? '通过' : scope.row.status == 2 ? '不通过' : '待审核'}}</div>
                            <div v-if="scope.row.status == 0">
                                <el-button
                                        size="mini"
                                        type="success"
                                        v-if="hasPermission('admin/user', 'update')"
                                        @click="check(scope.$index, scope.row, 'agree')">通过</el-button>
                                <el-button
                                        size="mini"
                                        type="danger"
                                        v-if="hasPermission('admin/user', 'update')"
                                        @click="check(scope.$index, scope.row, 'disagree')">不通过</el-button>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" width="75" v-if="hasPermission('admin/user', 'update') || hasPermission('admin/user', 'delete')">
                        <template slot-scope="scope">
                            <el-button
                                    size="mini"
                                    type="primary"
                                    :disabled="scope.row.id == 2"
                                    v-if="hasPermission('admin/user', 'update')"
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
                <div class="batch_delete" v-if="hasPermission('admin/user', 'delete')">
                    <el-button type="danger" :disabled="multipleSelection.length <= 0" @click="batchDelete">批量删除</el-button>
                    <el-button type="warning" :disabled="multipleSelection.length <= 0" @click="resetPwd">重置密码</el-button>
                </div>
            </div>

            <div class="updateWrapper" v-if="edit">
                <div class="title">请填写以下信息</div>
                <el-form :model="ruleForm" :rules="rules" ref="ruleForm" label-width="100px" class="demo-ruleForm" label-position="top" v-loading="loading">
                    <div class="form-item">
                        <div class="left">
                            <el-form-item label="客户名称" prop="name">
                                <el-input v-model="ruleForm.name" placeholder="请输入客户名称"></el-input>
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
                            <el-form-item label="客户备注" prop="desc">
                                <el-input v-model="ruleForm.desc" placeholder="请输入客户备注"></el-input>
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

            <div class="updateWrapper" v-if="editRole">
                <div class="title">{{activeUserName}}--用户角色</div>
                <div v-loading="loading" class="userRoleWrapper">
                    <br/>
                    <div class="roleItem" v-for="(item, key) in allRoles" v-if="item.name != '管理员' && item.name != '商家'">
                        <div class="name">
                            <el-checkbox v-if="1==2" v-model="item.selected">{{item.name}}</el-checkbox>
                            <el-radio border v-model="newRoleId" :label="item.id">{{item.name}}</el-radio>
                        </div>
                    </div>
                    <br/><br/>
                    <div class="editPermission" v-if="allRoles.length > 0">
                        <el-button type="primary" @click="submitRole()">提交</el-button>
                        <el-button type="warning" @click="cancelRole()">取消</el-button>
                    </div>
                </div>
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
                msg: 'USER',
                emptyText: '99',
                currentPage: 1,
                perPage: 10,
                total: 1,
                searchkey: '',
                searchRoleId: 'all',
                loading: false,
                edit: false,
                editRole: false,
                activeUserId: '',
                activeUserName: '',
                tableData: [],
                multipleSelection: [],
                ruleForm: {
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
                },
                allRoles: [],
                myRoles: [],
                roleOptions: [],
                newRoleId: ''
            }
        },
        methods:{
            handleSizeChange(val) {
                console.log(`每页 ${val} 条`);
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
            handleClick(index, row) {
                this.editRole = true;
                this.activeUserId = row.id;
                this.activeUserName = row.name;
                this.getUserRoles();
            },
            users(){
                var that = this;
                checkToken(function () {
                    that.loading = true;
                    that.axios.get('/admins', {
                        params : {
                            page : that.currentPage,
                            num: that.perPage,
                            search: that.searchkey,
                            roleid: that.searchRoleId,
                            privilege: encodeURIComponent(aesencode('read'))   //js加密后的字符串里面的+被浏览器解析成了空格，php解密的时候会出错，encodeURIComponent解决
                        }
                    })
                    .then(function (response) {
                        that.loading = false;
                        if(response.data.code == 0){
                            that.tableData = response.data.data.data;
                            that.total = response.data.data.total;
                        }
                        else{
                            Message.warning({
                                message: response.data.message
                            });
                        }
                    })
                    .catch(function (error) {
                        Message.error({
                            message: '未知错误'
                        });
                        that.loading = false;
                    });
                });
            },
            getUserRoles(){
                var that = this;
                checkToken(function () {
                    that.allRoles = [];
                    that.myRoles = [];
                    var url = '/admins/' + that.activeUserId + '/role';
                    that.loading = true;
                    that.axios.get(url, {
                        params: {
                            privilege: encodeURIComponent(aesencode('read'))
                        }
                    })
                    .then(function (response) {
                        if (response.data.code == 0) {
                            for (var i = 0; i < response.data.data.roles.length; i++) {
                                response.data.data.roles[i].selected = false;
                            }
                            that.allRoles = response.data.data.roles;
                            that.myRoles = response.data.data.myRoles;

                            if(that.myRoles.length > 0) {
                                for (var i = 0; i < that.allRoles.length; i++) {
                                    for (var j = 0; j < that.myRoles.length; j++) {
                                        if (that.myRoles[j].id == that.allRoles[i].id) {
                                            that.allRoles[i].selected = true;
                                            that.newRoleId = that.myRoles[j].id;
                                        }
                                    }
                                }
                            }
                            else{
                                that.newRoleId = '';
                            }
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
            submitRole(){
                var that = this;
                if(!that.newRoleId){
                    Message.warning({
                        message: '请选择角色'
                    });
                    return false;
                }

                checkToken(function () {
                    that.loading = true;
                    var url = '/admins/' + that.activeUserId + '/role';

                    //var newRoles = [];
                    //for (var i = 0; i < that.allRoles.length; i++) {
                        //if (that.allRoles[i].selected) {
                            //newRoles.push(that.allRoles[i].id);
                        //}
                    //}

                    //if(newRoles.length == 0){
                        //Message.warning({
                            //message: '请选择角色'
                        //});
                        //return false;
                    //}

                    that.axios.post(url, {
                        roles: [that.newRoleId],
                        privilege: encodeURIComponent(aesencode('update'))
                    })
                    .then(function (response) {
                        that.loading = false;
                        Message.success({
                            message: '插入成功'
                        });

                        that.cancelRole();
                        that.getUserRoles();
                        that.users();
                    })
                    .catch(function (error) {
                        that.loading = false;
                        Message.warning({
                            message: '未知错误'
                        });
                    });
                });
            },
            cancelRole(){
                this.editRole = false;
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
                this.ruleForm.name = row.name;
                this.ruleForm.desc = row.desc;
                this.ruleForm.email = row.email;
                this.ruleForm.contact = row.contact;
                this.ruleForm.phone = row.phone;
                this.ruleForm.area = row.area ? JSON.parse(row.area) : [];
                this.ruleForm.address = row.address;
                this.ruleForm.isopen = row.isopen == 1 ? true : false;
            },
            handleDelete(index, row) {
                var that = this;
                that.$confirm('此操作将删除该信息, 是否继续?', '提示', {
                    confirmButtonText: '确定',
                    cancelButtonText: '取消',
                    type: 'warning'
                }).then(() => {
                    checkToken(function () {
                        that.loading = true;
                        that.axios.post('/admins/delete', {
                            id: row.id,
                            privilege: encodeURIComponent(aesencode('delete'))
                        })
                        .then(function (response) {
                            that.loading = false;
                            Message.success({
                                message: '恭喜你，删除成功'
                            });

                            that.currentPage = 1;
                            that.total = 1;
                            that.tableData = [];
                            that.users();
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

                        that.axios.post('/admins/batchdelete', {
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
            check(index, row, status){
                var that = this;
                if(status == 'agree'){
                    var title = '确定要通过吗?';
                }
                else{
                    var title = '确定要不通过吗?';
                }

                that.$confirm(title, '提示', {
                    confirmButtonText: '确定',
                    cancelButtonText: '取消',
                    type: 'warning'
                }).then(() => {
                    checkToken(function () {
                        that.loading = true;
                        that.axios.post('/admins/check', {
                            status: status,
                            id: row.id,
                            privilege: encodeURIComponent(aesencode('update'))
                        })
                        .then(function (response) {
                            that.loading = false;
                            Message.success({
                                message: '提交成功'
                            });
                            that.users();
                        })
                        .catch(function (error) {
                            that.loading = false;
                            Message.warning({
                                message: '未知错误'
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
            chgRoleId(){
                this.search();
            },
            getRoles(){
                var that = this;
                checkToken(function () {
                    that.loading = true;
                    that.axios.get('/roles/lists', {
                        params: { //请求参数
                            privilege: encodeURIComponent(aesencode('read'))
                        }
                    })
                    .then(function (response) {
                        that.loading = false;
                        if (response.data.code == 0) {
                            that.roleOptions = response.data.data;
                            that.users();
                        }
                    })
                    .catch(function (error) {
                        Message.error({
                            message: '未知错误'
                        });
                        that.loading = false;
                    });
                });
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
            },
            selectable(row, index){
                if(row.id == 2){
                    return false;
                }
                else{
                    return true;
                }
            }
        },
        components: {
            PageTitle,
            CitySelect
        },
        mounted() {
            this.getRoles();
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
    .wrap-hundred .cell .inner-cell {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        width: 100%;
    }
</style>