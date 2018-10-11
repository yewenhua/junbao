<template>
    <div>
        <PageTitle title="价格模板"></PageTitle>
        <div class="pageWrapper">
            <div class="lookWrapper" v-show="!edit">
                <div class="searchWrapper">
                    <el-select v-model="searchType" placeholder="设备类型" @change="chgType" size="medium" style="width: 120px;">
                        <el-option key="全部类型" label="全部类型" value="all"></el-option>
                        <el-option
                                v-for="item in typeOptions"
                                :key="item.name"
                                :label="item.name"
                                :value="item.name">
                        </el-option>
                    </el-select>
                    <el-input placeholder="请输入内容" v-model="searchkey" size="medium">
                        <el-button slot="append" size="medium" icon="el-icon-search" @click="search">搜索</el-button>
                    </el-input>
                    <el-button type="primary" size="medium" class="addBtn" icon="el-icon-plus" @click="add" v-if="hasPermission('admin/pricetpl', 'add')">添加</el-button>
                </div>
                <el-table
                        ref="table"
                        :data="tableData"
                        stripe
                        border
                        empty-text="暂无数据"
                        v-loading="loading"
                        cell-class-name="txt-center"
                        header-cell-class-name="txt-center"
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
                            prop="username"
                            label="所属代理商">
                    </el-table-column>
                    <el-table-column
                            prop="ptype"
                            label="设备类型">
                    </el-table-column>
                    <el-table-column
                            prop="name"
                            label="模板名称">
                    </el-table-column>
                    <el-table-column
                            prop="signal_id"
                            label="充电时间">
                        <template slot-scope="scope">
                            <div>{{time(scope.row.signal_id)}}</div>
                        </template>
                    </el-table-column>
                    <el-table-column
                            prop="price"
                            label="消费价格（元）">
                    </el-table-column>
                    <el-table-column
                            prop="description"
                            class-name="description"
                            label="描述">
                    </el-table-column>
                    <el-table-column
                            prop="created_at"
                            label="创建时间"
                            width="180">
                        <template slot-scope="scope">
                            <div>{{scope.row.created_at ? scope.row.created_at.substring(0, 16) : ''}}</div>
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" width="150" v-if="hasPermission('admin/pricetpl', 'update') || hasPermission('admin/pricetpl', 'delete')">
                        <template slot-scope="scope">
                            <el-button
                                    size="mini"
                                    type="primary"
                                    v-if="hasPermission('admin/pricetpl', 'update')"
                                    @click="handleEdit(scope.$index, scope.row)">编辑</el-button>
                            <el-button
                                    size="mini"
                                    type="danger"
                                    v-if="hasPermission('admin/pricetpl', 'delete')"
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
                <div class="batch_delete" v-if="multipleSelection.length > 0 && hasPermission('admin/pricetpl', 'delete')">
                    <el-button type="danger" @click="batchDelete">批量删除</el-button>
                </div>
            </div>

            <div class="updateWrapper" v-show="edit">
                <div class="title">请填写以下信息</div>
                <el-form :model="ruleForm" :rules="rules" ref="ruleForm" label-width="100px" class="demo-ruleForm price" label-position="top" v-loading="loading">
                    <el-form-item label="设备分类" prop="title">
                        <el-select v-model="ruleForm.ptype" placeholder="请选择" style="width: 100%;">
                            <el-option
                                    v-for="item in typeOptions"
                                    :key="item.name"
                                    :label="item.name"
                                    :value="item.name">
                            </el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="模板名称" prop="name">
                        <el-input v-model="ruleForm.name" placeholder="请输入模板名称"></el-input>
                    </el-form-item>
                    <el-form-item label="充电时间" prop="signal_id">
                        <el-select v-model="ruleForm.signal_id" placeholder="请选择" style="width: 100%;">
                            <el-option
                                    v-for="item in priceOptions"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value">
                            </el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="消费价格（元）" prop="price">
                        <el-input v-model="ruleForm.price" placeholder="请输入消费价格"></el-input>
                    </el-form-item>
                    <el-form-item label="描述" prop="description">
                        <el-input v-model="ruleForm.description" placeholder="请输入描述"></el-input>
                    </el-form-item>
                    <br/>
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
    import { mapState } from 'vuex'
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
        Select,
        Option,
        Switch
    } from 'element-ui'
    import PageTitle from '../../frame/PageTitle.vue'
    import { checkToken }  from '../../ajax';
    import { aesencode, aesdecode }  from '../../utils';

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
    Vue.use(Select);
    Vue.use(Option);
    Vue.use(Switch);
    Vue.prototype.$confirm = MessageBox.confirm;
    Vue.prototype.$message = Message;

    export default {
        computed: {
            ...mapState(["domainUrl", 'baseURL', 'typeOptions', 'priceOptions']),
        },
        data(){
            return {
                currentPage: 1,
                perPage: 10,
                total: 1,
                searchkey: '',
                searchType: 'all',
                loading: false,
                edit: false,
                activeId: '',
                tableData: [],
                multipleSelection: [],
                ruleForm: {
                    ptype: '',
                    name: '',
                    signal_id: 0,
                    price: '',
                    description: ''
                },
                rules: {
                    name: [
                        { required: true, message: '请输入模板名称', trigger: 'blur' }
                    ],
                    price: [
                        { required: true, message: '请输入消费金额', trigger: 'blur' }
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
                this.lists();
            },
            add(){
                this.edit = true;
                this.activeId = '';
                this.ruleForm.ptype = '';
                this.ruleForm.name = '';
                this.ruleForm.signal_id = 0;
                this.ruleForm.price = '';
                this.ruleForm.description = '';
            },
            submitForm(formName) {
                var that = this;
                this.$refs[formName].validate((valid) => {
                    if (valid) {
                        checkToken(function () {
                            that.loading = true;
                            that.axios.post('/maintenance/pricestore', {
                                id: that.activeId,
                                name: that.ruleForm.name,
                                ptype: that.ruleForm.ptype,
                                signal_id: that.ruleForm.signal_id,
                                price: that.ruleForm.price,
                                description: that.ruleForm.description,
                                privilege: that.activeId ? encodeURIComponent(aesencode('update')) : encodeURIComponent(aesencode('add'))
                            })
                            .then(function (response) {
                                that.loading = false;
                                Message.success({
                                    message: '操作成功'
                                });

                                that.ruleForm.signal_id = '';
                                that.ruleForm.price = '';
                                that.ruleForm.description = '';
                                that.ruleForm.name = '';
                                that.ruleForm.ptype = '';
                                that.cancel();
                                that.lists();
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
            lists(){
                var that = this;
                checkToken(function () {
                    that.loading = true;
                    that.axios.get('/maintenance/pricetpl', {
                        params: { //请求参数
                            page: that.currentPage,
                            num: that.perPage,
                            search: that.searchkey,
                            type: that.searchType,
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
                this.lists()
            },
            chgType(){
                this.search();
            },
            handleEdit(index, row) {
                this.activeId = row.id;
                this.edit = true;
                this.ruleForm.ptype = row.ptype;
                this.ruleForm.name = row.name;
                this.ruleForm.signal_id = row.signal_id;
                this.ruleForm.price = row.price;
                this.ruleForm.description = row.description;
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
                        that.axios.post('/maintenance/pricebatchelete', {
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
                            that.lists();
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
            time(signal_id){
                let val = '';
                for(let i=0; i<this.priceOptions.length; i++){
                    if(this.priceOptions[i].value == signal_id){
                        val = this.priceOptions[i].label;
                        break;
                    }
                }
                return val;
            }
        },
        components: {
            PageTitle
        },
        mounted() {
            this.lists();
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
    .status.active{
        color: green;
    }
    .status.unactive{
        color: red;
    }
    .price .el-form-item {
        margin-bottom: 15px;
    }
</style>