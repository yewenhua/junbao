<template>
    <div>
        <PageTitle title="角色管理"></PageTitle>
        <div class="pageWrapper">
            <div class="lookWrapper" v-if="!edit && !editPermission">
                <div class="searchWrapper">
                    <el-input placeholder="请输入内容" v-model="searchkey" size="medium">
                        <el-button slot="append" size="medium" icon="el-icon-search" @click="search">搜索</el-button>
                    </el-input>
                    <el-button type="primary" size="medium" class="addBtn" icon="el-icon-plus" @click="addRole" v-if="hasPermission('admin/role', 'add') && 1==2">添加</el-button>
                </div>
                <el-table
                        :data="tableData"
                        stripe
                        border
                        empty-text="暂无数据"
                        v-loading="loading"
                        style="width: 100%">
                    <el-table-column
                            type="index"
                            :index="indexMethod">
                    </el-table-column>
                    <el-table-column
                            prop="name"
                            label="角色名称">
                    </el-table-column>
                    <el-table-column
                            prop="desc"
                            label="角色描述">
                    </el-table-column>
                    <el-table-column
                            prop="created_at"
                            label="创建日期">
                    </el-table-column>
                    <el-table-column
                            label="详情"
                            v-if="hasPermission('admin/role', 'add') && hasPermission('admin/role', 'delete') && hasPermission('admin/role', 'update') && hasPermission('admin/role', 'read')"
                            width="150">
                        <template slot-scope="scope">
                            <el-button @click="handleClick(scope.$index, scope.row)" type="text" size="small" :disabled="scope.row.id == 1 || scope.row.id == 6">角色权限</el-button>
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" width="150" v-if="(hasPermission('admin/role', 'update') || hasPermission('admin/role', 'delete')) && 1==2">
                        <template slot-scope="scope">
                            <el-button
                                    size="mini"
                                    type="primary"
                                    :disabled="scope.row.id == 1"
                                    v-if="hasPermission('admin/role', 'update')"
                                    @click="handleEdit(scope.$index, scope.row)">编辑</el-button>
                            <el-button
                                    size="mini"
                                    type="danger"
                                    :disabled="scope.row.id == 1 || scope.row.id == 6"
                                    v-if="hasPermission('admin/role', 'delete')"
                                    @click="handleDelete(scope.$index, scope.row)">删除</el-button>
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
            </div>

            <div class="updateWrapper" v-if="edit">
                <div class="title">请填写以下信息</div>
                <el-form :model="ruleForm" :rules="rules" ref="ruleForm" label-width="100px" class="demo-ruleForm" label-position="top" v-loading="loading">
                    <el-form-item label="角色名称" prop="name">
                        <el-input v-model="ruleForm.name" placeholder="请输入角色名称"></el-input>
                    </el-form-item>
                    <el-form-item label="角色描述" prop="desc">
                        <el-input v-model="ruleForm.desc" placeholder="请输入角色描述"></el-input>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="submitForm('ruleForm')">提交</el-button>
                        <el-button type="danger" @click="resetForm('ruleForm')">重置</el-button>
                        <el-button type="warning" @click="cancelForm()">取消</el-button>
                    </el-form-item>
                </el-form>
            </div>

            <div class="updateWrapper" v-if="editPermission">
                <div class="title">{{activeRoleName}}--角色权限</div>
                <div v-loading="loading" class="privilegeWrapper">
                    <div class="privilegeItem" v-for="(item, key) in allPermissions">
                        <div class="name">{{item.name}}</div>
                        <el-checkbox-group v-model="item.type">
                            <el-checkbox label="add" :name="'type' + item.desc" @change="writeChange(item.type)">增</el-checkbox>
                            <el-checkbox label="delete" :name="'type' + item.desc" @change="writeChange(item.type)">删</el-checkbox>
                            <el-checkbox label="update" :name="'type' + item.desc" @change="writeChange(item.type)">改</el-checkbox>
                            <el-checkbox label="read" :name="'type' + item.desc" @change="readChange(item.type)">查</el-checkbox>
                        </el-checkbox-group>
                    </div>
                    <div class="editPermission" v-if="allPermissions.length > 0">
                        <el-button type="primary" @click="submitPermission()">提交</el-button>
                        <el-button type="warning" @click="cancelPermission()">取消</el-button>
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
        MessageBox
    } from 'element-ui'
    import PageTitle from '../frame/PageTitle.vue'
    import { checkToken }  from '../ajax';
    import { aesencode, aesdecode }  from '../utils';

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
    Vue.prototype.$confirm = MessageBox.confirm;
    Vue.prototype.$message = Message;


    export default {
        data(){
            return {
                msg: 'ROLE',
                searchkey: '',
                currentPage: 1,
                loading: false,
                perPage: 10,
                total: 1,
                edit: false,
                editPermission: false,
                activeRoleId: '',
                activeRoleName: '',
                tableData: [],
                ruleForm: {
                    name: '',
                    desc: ''
                },
                rules: {
                    name: [
                        { required: true, message: '角色页面名称', trigger: 'blur' },
                    ],
                    desc: [
                        { required: true, message: '角色描述', trigger: 'blur' },
                    ]
                },
                allPermissions: [],
                myPermissions: []
            }
        },
        methods:{
            handleSizeChange(val) {
                console.log(`每页 ${val} 条`);
            },
            handleCurrentChange(val) {
                this.currentPage = val;
                this.roles();
            },
            addRole(){
                this.edit = true;
                this.activeRoleId = '';
                this.activeRoleName = '';
                this.ruleForm.name = '';
                this.ruleForm.desc = '';
            },
            submitForm(formName) {
                var that = this;
                this.$refs[formName].validate((valid) => {
                    if (valid) {
                        checkToken(function () {
                            that.loading = true;
                            that.axios.post('/roles/store', {
                                id: that.activeRoleId,
                                name: that.ruleForm.name,
                                desc: that.ruleForm.desc,
                                privilege: that.activeRoleId ? encodeURIComponent(aesencode('update')) : encodeURIComponent(aesencode('add'))
                            })
                            .then(function (response) {
                                that.loading = false;
                                Message.success({
                                    message: '恭喜你，插入成功'
                                });

                                that.ruleForm.name = '';
                                that.ruleForm.desc = '';
                                that.cancelForm();
                                that.roles();
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
            submitPermission(){
                var that = this;
                checkToken(function () {
                    that.loading = true;
                    var url = '/roles/' + that.activeRoleId + '/permission';

                    var newPermissions = [];
                    var newPermissionIds = [];
                    for (var i = 0; i < that.allPermissions.length; i++) {
                        if (that.allPermissions[i].type.length > 0) {
                            newPermissionIds.push(that.allPermissions[i].id);
                            newPermissions.push(that.allPermissions[i]);
                        }
                    }

                    if(newPermissions.length == 0){
                        Message.warning({
                            message: '请选择权限'
                        });
                        return false;
                    }

                    that.axios.post(url, {
                        permissions: newPermissionIds,
                        detail: newPermissions,
                        privilege: encodeURIComponent(aesencode('update'))
                    })
                    .then(function (response) {
                        that.loading = false;
                        Message.success({
                            message: '提交成功'
                        });

                        that.cancelPermission();
                        that.getRolePermissions();
                        that.userInfo();
                    })
                    .catch(function (error) {
                        that.loading = false;
                        Message.warning({
                            message: '未知错误'
                        });
                    });
                });
            },
            cancelPermission(){
                this.editPermission = false;
            },
            roles(){
                var that = this;
                checkToken(function () {
                    that.loading = true;
                    that.axios.get('/roles', {
                        params: { //请求参数
                            page: that.currentPage,
                            num: that.perPage,
                            search: that.searchkey,
                            privilege: encodeURIComponent(aesencode('read'))
                        }
                    })
                    .then(function (response) {
                        if (response.data.code == 0) {
                            that.tableData = response.data.data.data;
                            that.total = response.data.data.total;
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
            getRolePermissions(){
                var that = this;
                checkToken(function () {
                    that.allPermissions = [];
                    that.myPermissions = [];
                    var url = '/roles/' + that.activeRoleId + '/permission';
                    that.loading = true;
                    that.axios.get(url, {
                        params: {
                            privilege: encodeURIComponent(aesencode('read'))
                        }
                    })
                    .then(function (response) {
                        if (response.data.code == 0) {
                            for (var i = 0; i < response.data.data.permissions.length; i++) {
                                response.data.data.permissions[i].type = [];
                            }
                            that.allPermissions = response.data.data.permissions;
                            that.myPermissions = response.data.data.myPermissions;

                            for (var i = 0; i < that.allPermissions.length; i++) {
                                for (var j = 0; j < that.myPermissions.length; j++) {
                                    if (that.allPermissions[i].id == that.myPermissions[j].id) {
                                        that.allPermissions[i].type = ['read'];
                                        if (that.myPermissions[j].pivot.add_permission == 1) {
                                            that.allPermissions[i].type.push('add');
                                        }
                                        if (that.myPermissions[j].pivot.delete_permission == 1) {
                                            that.allPermissions[i].type.push('delete');
                                        }
                                        if (that.myPermissions[j].pivot.update_permission == 1) {
                                            that.allPermissions[i].type.push('update');
                                        }
                                    }
                                }
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
            writeChange(value){
                if(!this.hasRead(value) && this.isAssignRead(value)){
                    //没有read，有增删改中的至少一项权限，赋予read权限
                    value.push('read');
                }
            },
            readChange(value){
                if(!this.hasRead(value)){
                    //没有read，其他权限一律取消
                    value.splice(0, value.length)
                }
            },
            hasRead(array){
                var flag = false;
                for(var i=0; i<array.length; i++){
                    if(array[i] == 'read'){
                        flag = true;
                        break;
                    }
                }

                return flag;
            },
            isAssignRead(array){
                var flag = false;
                for(var i=0; i<array.length; i++){
                    if(array[i] == 'add' || array[i] == 'delete' || array[i] == 'update'){
                        flag = true;
                        break;
                    }
                }

                return flag;
            },
            handleClick(index, row) {
                this.editPermission = true;
                this.activeRoleId = row.id;
                this.activeRoleName= row.name;
                this.getRolePermissions();
            },
            indexMethod(index){
                return index + 1;
            },
            search(){
                this.currentPage = 1;
                this.total = 1;
                this.tableData = [];
                this.roles()
            },
            handleEdit(index, row) {
                this.activeRoleId = row.id;
                this.activeRoleName = row.name;
                this.edit = true;
                this.ruleForm.name = row.name;
                this.ruleForm.desc = row.desc;
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
                        that.axios.post('/roles/delete', {
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
                            that.roles();
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
            userInfo(){
                var that = this;
                checkToken(function () {
                    that.axios.get('/admins/info', {
                        params: { //请求参数

                        }
                    })
                    .then(function (response) {
                        if (response.data.code == 0) {
                            var userInfo = response.data.data;
                            var roles = response.data.data.roles;
                            var permissions = [];
                            for(var i=0; i<roles.length; i++){
                                for(var j=0; j<roles[i].permissions.length; j++){
                                    permissions.push(roles[i].permissions[j]);
                                }
                            }

                            that.$store.dispatch('permissions', {
                                permissions: permissions
                            });

                            that.$store.dispatch('userInfo', {
                                userInfo: userInfo
                            });
                        }
                    })
                    .catch(function (error) {
                        Message.error({
                            message: '未知错误'
                        });
                    });
                });
            },
        },
        components: {
            PageTitle
        },
        mounted() {
            this.roles();
        }
    }
</script>
<style scoped>
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
    .privilegeWrapper{
        min-height: 200px;
    }
    .privilegeItem{
        border: 1px solid #cfdadd;
        border-radius: 3px;
        padding: 5px 10px;
        margin-right: 12px;
        margin-bottom: 12px;
        display: inline-block;
    }
    .privilegeItem .name{
        text-align: center;
        font-weight: 700;
        line-height: 30px;
    }
    .el-checkbox+.el-checkbox{
        margin-left: 12px;
    }
    .editPermission{
        margin-top: 10px;
    }
</style>