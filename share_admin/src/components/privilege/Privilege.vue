<template>
    <div>
        <PageTitle title="权限管理"></PageTitle>
        <div class="pageWrapper">
            <div class="lookWrapper" v-show="!edit">
                <div class="searchWrapper">
                    <el-input placeholder="请输入内容" v-model="searchkey" size="medium">
                        <el-button slot="append" size="medium" icon="el-icon-search" @click="search">搜索</el-button>
                    </el-input>
                    <el-button type="primary" size="medium" class="addBtn" icon="el-icon-plus" @click="add" v-if="hasPermission('admin/privilege', 'add')">添加</el-button>
                </div>
                <el-table
                        :data="tableData"
                        stripe
                        border
                        empty-text="暂无数据"
                        v-loading="loading"
                        @selection-change="handleSelectionChange"
                        style="width: 100%">
                    <el-table-column
                            type="selection"
                            width="55">
                    </el-table-column>
                    <el-table-column
                            type="index"
                            :index="indexMethod">
                    </el-table-column>
                    <el-table-column
                            prop="name"
                            label="名称"
                            width="180">
                    </el-table-column>
                    <el-table-column
                            prop="desc"
                            label="权限页面URL">
                    </el-table-column>
                    <el-table-column
                            prop="created_at"
                            label="创建日期"
                            width="180">
                    </el-table-column>
                    <el-table-column label="操作" width="150" v-if="hasPermission('admin/privilege', 'update') || hasPermission('admin/privilege', 'delete')">
                        <template slot-scope="scope">
                            <el-button
                                    size="mini"
                                    type="primary"
                                    v-if="hasPermission('admin/privilege', 'update')"
                                    @click="handleEdit(scope.$index, scope.row)">编辑</el-button>
                            <el-button
                                    size="mini"
                                    type="danger"
                                    v-if="hasPermission('admin/privilege', 'delete')"
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
                <div class="batch_delete" v-if="multipleSelection.length > 0 && hasPermission('admin/privilege', 'delete')">
                    <el-button type="danger" @click="batchDelete">批量删除</el-button>
                </div>
            </div>

            <div class="updateWrapper" v-show="edit">
                <div class="title">请填写以下信息</div>
                <el-form :model="ruleForm" :rules="rules" ref="ruleForm" label-width="100px" class="demo-ruleForm" label-position="top" v-loading="loading">
                    <el-form-item label="权限页面名称" prop="name">
                        <el-input v-model="ruleForm.name" placeholder="请输入权限名称"></el-input>
                    </el-form-item>
                    <el-form-item label="权限页面URL" prop="desc">
                        <el-input v-model="ruleForm.desc" placeholder="请输入权限URL"></el-input>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="submitForm('ruleForm')">提交</el-button>
                        <el-button type="danger" @click="resetForm('ruleForm')">重置</el-button>
                        <el-button type="warning" @click="cancel()">取消</el-button>
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
                msg: 'PRIVILEGE',
                currentPage: 1,
                perPage: 10,
                total: 1,
                searchkey: '',
                loading: false,
                edit: false,
                activePermissionId: '',
                tableData: [],
                multipleSelection: [],
                ruleForm: {
                    name: '',
                    desc: ''
                },
                rules: {
                    name: [
                        { required: true, message: '权限页面名称', trigger: 'blur' }
                    ],
                    desc: [
                        { required: true, message: '权限页面URL', trigger: 'blur' }
                    ]
                }
            }
        },
        methods:{
            handleSizeChange(val) {
                console.log(`每页 ${val} 条`);
            },
            handleCurrentChange(val) {
                this.currentPage = val;
                this.permissions();
            },
            add(){
                this.edit = true;
                this.activePermissionId = '';
                this.ruleForm.name = '';
                this.ruleForm.desc = '';
            },
            submitForm(formName) {
                var that = this;
                this.$refs[formName].validate((valid) => {
                    if (valid) {
                        checkToken(function () {
                            that.loading = true;
                            that.axios.post('/permissions/store', {
                                id: that.activePermissionId,
                                name: that.ruleForm.name,
                                desc: that.ruleForm.desc,
                                privilege: that.activePermissionId ? encodeURIComponent(aesencode('update')) : encodeURIComponent(aesencode('add'))
                            })
                            .then(function (response) {
                                that.loading = false;
                                Message.success({
                                    message: '恭喜你，插入成功'
                                });

                                that.ruleForm.name = '';
                                that.ruleForm.desc = '';
                                that.cancel();
                                that.permissions();
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
            permissions(){
                var that = this;
                checkToken(function () {
                    that.loading = true;
                    that.axios.get('/permissions', {
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
            cancel(){
                this.edit = false;
            },
            indexMethod(index){
                return index + 1;
            },
            search(){
                this.currentPage = 1;
                this.total = 1;
                this.tableData = [];
                this.permissions()
            },
            handleEdit(index, row) {
                this.activePermissionId = row.id;
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
                        that.axios.post('/permissions/delete', {
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
                            that.permissions();
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
            handleSelectionChange(val){
                this.multipleSelection = val;
            },
            batchDelete(){
                var that = this;
                if(that.multipleSelection.length <= 0){
                    Message.warning({
                        message: '请选择要删除的选项'
                    });
                    return false;
                }

                that.$confirm('此操作将删除该信息, 是否继续?', '提示', {
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
                        that.axios.post('/permissions/batchdelete', {
                            idstring: idstring,
                            privilege: encodeURIComponent(aesencode('delete'))
                        })
                        .then(function (response) {
                            that.loading = false;
                            Message.success({
                                message: '删除成功'
                            });

                            that.currentPage = 1;
                            that.total = 1;
                            that.tableData = [];
                            that.permissions();
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
            }
        },
        components: {
            PageTitle
        },
        mounted() {
            this.permissions();
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
</style>