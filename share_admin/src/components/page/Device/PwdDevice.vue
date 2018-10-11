<template>
    <div>
        <PageTitle title="手机充电器"></PageTitle>
        <div class="pageWrapper">
            <div class="lookWrapper" v-show="!edit">
                <div class="searchWrapper">
                    <el-select v-model="searchUid" placeholder="代理商" @change="chgUid" size="medium" style="width: 130px;">
                        <el-option key="全部用户" label="全部用户" value="all" v-if="usertype=='admin'"></el-option>
                        <el-option :key="selfName" :label="selfName" value="agent" v-if="usertype=='agent'"></el-option>
                        <el-option
                                v-for="item in agents"
                                :key="item.id"
                                :label="item.name"
                                :value="item.id">
                        </el-option>
                    </el-select>
                    <el-select v-model="searchType" placeholder="设备类型" @change="chgType" size="medium" style="width: 120px;">
                        <el-option key="全部类型" label="全部类型" value="all"></el-option>
                        <el-option
                                v-for="item in typeOptions"
                                :key="item.name"
                                :label="item.name"
                                :value="item.name">
                        </el-option>
                    </el-select>
                    <el-select v-model="searchBrand" placeholder="设备品牌" @change="chgBrand" size="medium" style="width: 120px;">
                        <el-option key="全部类型" label="全部类型" value="all"></el-option>
                        <el-option
                                v-for="item in brandOptions"
                                :key="item.name"
                                :label="item.name"
                                :value="item.name">
                        </el-option>
                    </el-select>
                    <el-select v-model="isActive" placeholder="请选择" size="medium" @change="activeChg" style="width: 110px;">
                        <el-option key="全部" label="全部状态" :value="'all'"></el-option>
                        <el-option key="已激活" label="已激活" :value="'yes'"></el-option>
                        <el-option key="未激活" label="未激活" :value="'no'"></el-option>
                    </el-select>
                    <el-input placeholder="设备编号" v-model="searchkey" size="medium" style="width: 210px;">
                        <el-button slot="append" size="medium" icon="el-icon-search" @click="search">搜索</el-button>
                    </el-input>
                    <el-button type="primary" size="small" icon="el-icon-download" @click="zip" v-if="hasPermission('admin/pwddevice', 'read')">下载二维码</el-button>
                    <el-button type="primary" size="small" class="addBtn" icon="el-icon-plus" @click="add" v-if="hasPermission('admin/pwddevice', 'add') && usertype=='admin'">添加</el-button>
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
                            width="50">
                    </el-table-column>
                    <el-table-column
                            type="index"
                            :index="indexMethod">
                    </el-table-column>
                    <el-table-column
                            prop="type"
                            label="设备类型"
                            width="120">
                    </el-table-column>
                    <el-table-column
                            prop="brand"
                            label="设备品牌"
                            width="120">
                    </el-table-column>
                    <el-table-column
                            prop="sn"
                            label="设备编号">
                    </el-table-column>
                    <el-table-column
                            prop="username"
                            label="所属代理商">
                    </el-table-column>
                    <el-table-column
                            prop="address"
                            label="摆放位置">
                    </el-table-column>
                    <el-table-column
                            prop="location"
                            label="场地名称">
                    </el-table-column>
                    <el-table-column
                            prop="isopen"
                            label="设备状态"
                            width="80">
                        <template slot-scope="scope">
                            <div class="status" :class="scope.row.isopen == 1 ? 'active' : 'unactive'">{{scope.row.isopen == 1 ? '启用中' : '停用中'}}</div>
                        </template>
                    </el-table-column>
                    <el-table-column
                            prop="isopen"
                            label="是否激活"
                            width="80">
                        <template slot-scope="scope">
                            <div class="status" :class="scope.row.active == 1 ? 'active' : 'unactive'">{{scope.row.active == 1 ? '已激活' : '未激活'}}</div>
                        </template>
                    </el-table-column>
                    <el-table-column
                            prop="created_at"
                            label="创建时间"
                            width="100">
                        <template slot-scope="scope">
                            <div>{{scope.row.created_at ? scope.row.created_at.substring(0, 10) : ''}}</div>
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" width="80" v-if="hasPermission('admin/pwddevice', 'update') || hasPermission('admin/pwddevice', 'delete')">
                        <template slot-scope="scope">
                            <el-button
                                    size="mini"
                                    type="primary"
                                    v-if="hasPermission('admin/pwddevice', 'update')"
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
                <div class="batch_delete" v-if="hasPermission('admin/pwddevice', 'update')">
                    <el-button type="primary" :disabled="multipleSelection.length <= 0" @click="batchEdit">批量编辑</el-button>
                    <el-button type="danger" :disabled="multipleSelection.length <= 0" @click="batchDelete" v-if="usertype=='admin' && hasPermission('admin/pwddevice', 'delete')">批量删除</el-button>
                    <el-button type="warning" :disabled="multipleSelection.length <= 0" @click="batchCancel">设备解绑</el-button>
                    <el-button type="success" :disabled="multipleSelection.length <= 0" @click="batchReset">设备重置</el-button>
                    <el-button type="info" :disabled="tableData.length <= 0" @click="batchAssign" v-if="usertype=='agent'">分配子账户</el-button>
                    <el-button type="info" :disabled="tableData.length <= 0" @click="adminAssign" v-if="usertype=='admin'">批量分配</el-button>
                </div>

                <el-dialog title="设备批量分配" :visible.sync="dialogAssignVisible" :modal-append-to-body="false" class="child-dialog">
                    <el-form :model="ruleAssignForm" :rules="rulesAssign" ref="ruleAssignForm" label-width="100px" class="demo-ruleForm" label-position="top" v-loading="loading">
                        <el-form-item label="分配方式" prop="type">
                            <el-radio v-model="ruleAssignForm.type" :label="1">号段批量分配</el-radio>
                            <el-radio v-model="ruleAssignForm.type" :label="2">批量手工分配</el-radio>
                        </el-form-item>
                        <el-form-item label="批量分配" v-if="ruleAssignForm.type == 1">
                            <el-input  v-model="ruleAssignForm.start" style="width: 200px;" placeholder="请输入开始号段"></el-input> - <el-input  v-model="ruleAssignForm.end" style="width: 200px;" placeholder="请输入结束号段"></el-input>
                        </el-form-item>
                        <el-form-item label="设备编号" prop="sn" v-if="ruleAssignForm.type == 2">
                            <el-input type="textarea" :rows="3" v-model="ruleAssignForm.sn" placeholder="请输入设备编号（多个请按空格隔开）"></el-input>
                        </el-form-item>
                        <el-form-item label="分配用户" prop="uid" v-if="usertype=='admin'">
                            <el-select v-model="ruleAssignForm.uid" placeholder="代理商" size="medium" style="width: 200px;">
                                <el-option
                                        v-for="item in agents"
                                        :key="item.id"
                                        :label="item.name"
                                        :value="item.id">
                                </el-option>
                            </el-select>
                        </el-form-item>
                        <el-form-item label="分配用户" prop="uid" v-if="usertype=='agent'">
                            <el-select v-model="ruleAssignForm.uid" placeholder="代理商" size="medium" style="width: 200px;">
                                <el-option
                                        v-for="item in children"
                                        :key="item.id"
                                        :label="item.name"
                                        :value="item.id">
                                </el-option>
                            </el-select>
                        </el-form-item>
                        <el-form-item>
                            <el-button type="primary" @click="submitAssignForm('ruleAssignForm')">提交分配</el-button>
                        </el-form-item>
                    </el-form>
                </el-dialog>
            </div>

            <div class="updateWrapper device" v-show="edit" v-if="hasPermission('admin/pwddevice', 'update')">
                <div class="title">请填写以下信息</div>
                <el-form :model="ruleForm" :rules="rules" ref="ruleForm" label-width="100px" class="demo-ruleForm" label-position="top" v-loading="loading">
                    <el-form-item label="代理商" prop="uid">
                        <el-select v-model="ruleForm.uid" placeholder="请选择" style="width: 100%;">
                            <el-option key="不分配" label="不分配" :value="0" v-if="usertype=='admin'"></el-option>
                            <el-option
                                    v-for="item in agents"
                                    :key="item.id"
                                    :label="item.name"
                                    :value="item.id">
                            </el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="设备分类" prop="type">
                        <el-select v-model="ruleForm.type" @change="listenType" placeholder="请选择" style="width: 100%;">
                            <el-option
                                    v-for="item in typeOptions"
                                    :key="item.name"
                                    :label="item.name"
                                    :value="item.name">
                            </el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="设备品牌" prop="brand">
                        <el-select v-model="ruleForm.brand" placeholder="请选择" style="width: 100%;">
                            <el-option
                                    v-for="item in brandOptions"
                                    :key="item.name"
                                    :label="item.name"
                                    :value="item.name">
                            </el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="设备编号" prop="sn">
                        <el-input type="textarea" :rows="3" :disabled="activeId != ''" v-model="ruleForm.sn" placeholder="请输入设备编号（多个请按空格隔开）"></el-input>
                    </el-form-item>
                    <el-form-item label="摆放位置" prop="address">
                        <el-input v-model="ruleForm.address" placeholder="请输入摆放位置"></el-input>
                    </el-form-item>
                    <el-form-item label="场地名称" prop="location">
                        <el-input v-model="ruleForm.location" placeholder="请输入场地名称"></el-input>
                    </el-form-item>
                    <el-form-item label="价格规则" prop="pricelist" v-loading="tpling">
                        <el-checkbox-group v-model="ruleForm.pricelist">
                            <el-checkbox :label="item.id" v-for="item in ptpl" :key="item.id">{{item.name + '/' + item.price + '元'}}</el-checkbox>
                        </el-checkbox-group>
                    </el-form-item>
                    <el-form-item label="状态是否可用" prop="isopen">
                        <el-switch
                                v-model="ruleForm.isopen"
                                active-color="#409eff"
                                inactive-color="#dcdfe6">
                        </el-switch>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="submitForm('ruleForm')" :disabled="tpling">提交</el-button>
                        <el-button type="danger" @click="resetForm('ruleForm')">重置</el-button>
                        <el-button type="warning" @click="cancel()">取消</el-button>
                    </el-form-item>
                </el-form>
                <div class="device_img" v-if="ruleForm.qrimg && !batch">
                    <img :src="ruleForm.qrimg"/>
                </div>
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
        Message,
        Loading,
        MessageBox,
        Select,
        Option,
        Switch,
        Checkbox,
        CheckboxButton,
        CheckboxGroup,
        Dialog
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
    Vue.use(Loading);
    Vue.use(Select);
    Vue.use(Option);
    Vue.use(Switch);
    Vue.use(Checkbox);
    Vue.use(CheckboxButton);
    Vue.use(CheckboxGroup);
    Vue.use(Dialog);
    Vue.prototype.$confirm = MessageBox.confirm;
    Vue.prototype.$message = Message;

    export default {
        computed: {
            ...mapState(["domainUrl", 'baseURL', 'typeOptions', 'brandOptions', 'userInfo']),
        },
        data(){
            return {
                currentPage: 1,
                perPage: 10,
                total: 1,
                searchkey: '',
                searchType: 'all',
                searchBrand: 'all',
                searchUid: 'all',
                isActive: 'all',
                loading: false,
                tpling: false,
                edit: false,
                batch: false,
                activeId: '',
                tableData: [],
                multipleSelection: [],
                ptpl: [],
                agents: [],
                usertype: 'agent',
                selfName: '',
                ruleForm: {
                    brand: '',
                    type: '',
                    sn: '',
                    uid: 0,
                    qrimg: '',
                    location: '',
                    address: '',
                    pricelist:[],
                    isopen: true
                },
                rules: {
                    brand: [
                        { required: true, message: '请输入设备品牌', trigger: 'blur' }
                    ],
                    type: [
                        { required: true, message: '请输入设备分类', trigger: 'blur' }
                    ],
                    uid: [
                        { required: true, message: '请选择代理商', trigger: 'blur' }
                    ],
                    pricelist: [
                        { required: true, message: '请选择价格规则', trigger: 'blur' }
                    ],
                    sn: [
                        { required: true, message: '请输入设备编号', trigger: 'blur' }
                    ]
                },
                dialogAssignVisible: false,
                children: [],
                assignUid: '',
                ruleAssignForm: {
                    type: 1,
                    start: '',
                    end: '',
                    sn: '',
                    uid: ''
                },
                rulesAssign: {
                    type: [
                        { required: true, message: '请选择分配方式', trigger: 'blur' }
                    ],
                    uid: [
                        { required: true, message: '请选择分配用户', trigger: 'blur' }
                    ]
                },
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
                this.ruleForm.uid = this.usertype=='agent' ? this.agents[0].id : 0;
                this.ruleForm.brand = '';
                this.ruleForm.sn = '';
                this.ruleForm.type = '';
                this.ruleForm.qrimg = '';
                this.ruleForm.address = '';
                this.ruleForm.location = '';
                this.ruleForm.isopen = true;
                this.getPtpl();
            },
            submitForm(formName) {
                var that = this;
                this.$refs[formName].validate((valid) => {
                    if (valid) {
                        checkToken(function () {
                            that.loading = true;
                            that.axios.post('/devices/store', {
                                id: that.activeId,
                                uid: that.ruleForm.uid,
                                brand: that.ruleForm.brand,
                                sn: that.ruleForm.sn,
                                type: that.ruleForm.type,
                                address: that.ruleForm.address,
                                location: that.ruleForm.location,
                                pricelist: that.ruleForm.pricelist,
                                isopen: that.ruleForm.isopen ? 1 : 0,
                                category: '手机充电器',
                                privilege: that.activeId ? encodeURIComponent(aesencode('update')) : encodeURIComponent(aesencode('add'))
                            })
                            .then(function (response) {
                                that.loading = false;
                                that.batch = false;
                                if (response.data.code == 0) {
                                    Message.success({
                                        message: '操作成功'
                                    });

                                    that.ruleForm.brand = '';
                                    that.ruleForm.sn = '';
                                    that.ruleForm.type = '';
                                    that.ruleForm.address = '';
                                    that.ruleForm.location = '';
                                    that.ruleForm.isopen = true;
                                    that.cancel();
                                    that.lists();
                                }
                                else{
                                    Message.warning({
                                        message: response.data.message
                                    });
                                }
                            })
                            .catch(function (error) {
                                that.batch = false;
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
                this.batch = false;
            },
            lists(){
                var that = this;
                checkToken(function () {
                    that.loading = true;
                    that.axios.get('/devices', {
                        params: { //请求参数
                            page: that.currentPage,
                            num: that.perPage,
                            search: that.searchkey,
                            type: that.searchType,
                            brand: that.searchBrand,
                            uid: that.searchUid,
                            isactive: that.isActive,
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
                this.batch = false;
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
            chgBrand(){
                this.search();
            },
            chgUid(){
                this.search();
            },
            handleEdit(index, row) {
                this.activeId = row.id;
                this.edit = true;
                this.ruleForm.uid = row.uid;
                this.ruleForm.brand = row.brand;
                this.ruleForm.sn = row.sn;
                this.ruleForm.type = row.type;
                this.ruleForm.location = row.location;
                this.ruleForm.address = row.address;
                this.ruleForm.qrimg = row.qrimg;

                let ids = [];
                for(let i=0; i<row.ptpls.length; i++){
                    ids.push(row.ptpls[i].id);
                }
                this.ruleForm.pricelist = ids;
                this.ruleForm.isopen = row.isopen == 1 ? true : false;
                this.getPtpl();
            },
            batchEdit(){
                var that = this;
                if(that.multipleSelection.length <= 0){
                    Message.warning({
                        message: '请选择要删除的选项'
                    });
                    return false;
                }

                let idstring = '';
                let snstring = '';
                for(let i=0; i<that.multipleSelection.length; i++){
                    if(idstring){
                        idstring += ',' + that.multipleSelection[i].id;
                        snstring += ' ' + that.multipleSelection[i].sn;
                    }
                    else{
                        idstring = that.multipleSelection[i].id;
                        snstring = that.multipleSelection[i].sn;
                    }
                }

                this.batch = true;
                this.activeId = idstring;
                this.edit = true;
                this.ruleForm.uid = this.usertype=='agent' ? this.agents[0].id : 0;
                this.ruleForm.brand = '';
                this.ruleForm.sn = snstring;
                this.ruleForm.type = '';
                this.ruleForm.location = '';
                this.ruleForm.address = '';
                this.ruleForm.isopen = true;
                this.getPtpl();
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
                        that.axios.post('/devices/delete', {
                            id: row.id,
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
                        that.axios.post('/devices/batchdelete', {
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
            zip(){
                if(this.multipleSelection.length <= 0){
                    Message.warning({
                        message: '请勾选要下载的选项'
                    });
                    return false;
                }

                let idstring = '';
                for(let i=0; i<this.multipleSelection.length; i++){
                    if(idstring){
                        idstring += ',' + this.multipleSelection[i].id;
                    }
                    else{
                        idstring = this.multipleSelection[i].id;
                    }
                }
                let url = this.baseURL + "devices/qrcode?idstring=" + idstring;
                window.open(url);
            },
            getPtpl(){
                var that = this;
                checkToken(function () {
                    that.tpling = true;
                    that.axios.get('/maintenance/ptplagent', {
                        params: { //请求参数
                            type: that.ruleForm.type,
                            privilege: encodeURIComponent(aesencode('read'))
                        }
                    })
                    .then(function (response) {
                        if (response.data.code == 0) {
                            that.ptpl = response.data.data;
                        }
                        that.tpling = false;
                    })
                    .catch(function (error) {
                        Message.error({
                            message: '未知错误'
                        });
                        that.tpling = false;
                    });
                });
            },
            listenType(){
                this.getPtpl();
            },
            batchCancel(){
                var that = this;
                if(that.multipleSelection.length <= 0){
                    Message.warning({
                        message: '请选择要解绑的选项'
                    });
                    return false;
                }

                that.$confirm('此操作将解绑设备, 是否继续?', '提示', {
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
                        that.axios.post('/devices/batchcancel', {
                            idstring: idstring,
                            privilege: encodeURIComponent(aesencode('delete'))
                        })
                        .then(function (response) {
                            that.loading = false;
                            Message.success({
                                message: '解绑成功'
                            });

                            that.currentPage = 1;
                            that.total = 1;
                            that.tableData = [];
                            that.lists();
                        })
                        .catch(function (error) {
                            that.loading = false;
                            Message.warning({
                                message: '解绑失败'
                            });
                        });
                    });
                }).catch((error) => {

                });
            },
            batchReset(){
                var that = this;
                if(that.multipleSelection.length <= 0){
                    Message.warning({
                        message: '请选择要重置的选项'
                    });
                    return false;
                }

                that.$confirm('此操作将重置设备, 是否继续?', '提示', {
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
                        that.axios.post('/devices/batchreset', {
                            idstring: idstring,
                            privilege: encodeURIComponent(aesencode('delete'))
                        })
                        .then(function (response) {
                            that.loading = false;
                            Message.success({
                                message: '重置成功'
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
            batchAssign(){
                this.allchild();
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
                                that.selfName = that.agents[0].name;
                                that.agents[0].name = '所有用户';
                                that.searchUid = that.agents[0].id;
                                that.ruleForm.uid = that.agents[0].id;
                            }
                            that.lists();
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
            allchild(){
                var that = this;
                checkToken(function () {
                    that.loading = true;
                    that.axios.get('/admins/allchild', {
                        params: { //请求参数
                            privilege: encodeURIComponent(aesencode('read'))
                        }
                    })
                    .then(function (response) {
                        if (response.data.code == 0) {
                            that.children = response.data.data;
                            if(that.children.length > 0){
                                that.dialogAssignVisible = true;
                                that.assignUid = that.children[0].id;
                            }
                            else{
                                Message.warning({
                                    message: '名下还没有商家'
                                });
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
            adminAssign(){
                this.dialogAssignVisible  = true;
            },
            submitAssignForm(formName){
                var that = this;
                if(that.ruleAssignForm.type == 1){
                    if(!that.ruleAssignForm.start){
                        Message.warning({
                            message: '请输入开始号段'
                        });
                        return false;
                    }
                    if(!that.ruleAssignForm.end){
                        Message.warning({
                            message: '请输入结束号段'
                        });
                        return false;
                    }
                }
                else{
                    if(!that.ruleAssignForm.sn){
                        Message.warning({
                            message: '请输入设备编号'
                        });
                        return false;
                    }
                }

                if(!that.ruleAssignForm.uid){
                    Message.warning({
                        message: '请选择分配用户'
                    });
                    return false;
                }

                this.$refs[formName].validate((valid) => {
                    if (valid) {
                        checkToken(function () {
                            that.loading = true;
                            that.axios.post('/devices/diyassign', {
                                type: that.ruleAssignForm.type,
                                start: that.ruleAssignForm.start,
                                end: that.ruleAssignForm.end,
                                sn: that.ruleAssignForm.sn,
                                uid: that.ruleAssignForm.uid,
                                privilege: that.activeId ? encodeURIComponent(aesencode('update')) : encodeURIComponent(aesencode('add'))
                            })
                            .then(function (response) {
                                that.loading = false;
                                if (response.data.code == 0) {
                                    that.dialogAssignVisible  = false;
                                    Message.success({
                                        message: '操作成功'
                                    });

                                    that.ruleAssignForm.start = '';
                                    that.ruleAssignForm.end = '';
                                    that.ruleAssignForm.sn = '';
                                    that.ruleAssignForm.uid = '';
                                    that.lists();
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
            activeChg(){
                this.currentPage = 1;
                this.total = 1;
                this.tableData = [];
                this.lists()
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
    .status.active{
        color: green;
    }
    .status.unactive{
        color: red;
    }
    .device .el-form-item {
        margin-bottom: 10px;
    }
    .device{
        position: relative;
    }
    .device .device_img{
        position: absolute;
        right: 20px;
        bottom: 50px;
        width: 200px;
        height: 200px;
        text-align: right;
    }
    .device .device_img img{
        height: 100%;
        width: 100%;
    }
    .device .device_sn{
        text-align: center;
        margin-top: -8px;
    }
</style>