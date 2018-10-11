<template>
    <div>
        <PageTitle title="市场维护"></PageTitle>
        <div class="pageWrapper">
            <div class="lookWrapper">
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
                            width="75">
                    </el-table-column>
                    <el-table-column
                            type="index"
                            :index="indexMethod">
                    </el-table-column>
                    <el-table-column
                            prop="name"
                            label="用户名">
                    </el-table-column>
                    <el-table-column
                            prop="phone"
                            label="手机号码">
                    </el-table-column>
                    <el-table-column
                            prop="agents"
                            label="代理商">
                        <template slot-scope="scope">
                            <el-button @click="showAgents(scope.$index, scope.row)" type="text" size="medium">名下代理商</el-button>
                        </template>
                    </el-table-column>
                    <el-table-column
                            prop="qrimg"
                            label="二维码">
                        <template slot-scope="scope">
                            <el-popover
                                    placement="left"
                                    width="400"
                                    trigger="hover">
                                <img class="pop_img" :src="scope.row.qrimg"/>
                                <img class="face_img" :src="scope.row.qrimg" slot="reference"/>
                            </el-popover>
                        </template>
                    </el-table-column>
                    <el-table-column
                            prop="openid"
                            label="是否绑定微信">
                        <template slot-scope="scope">
                            <div>{{scope.row.openid ? 是 : '否'}}</div>
                        </template>
                    </el-table-column>
                    <el-table-column
                            prop="created_at"
                            width="180"
                            label="创建时间">
                        <template slot-scope="scope">
                            <div>{{scope.row.created_at ? scope.row.created_at : ''}}</div>
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
                <div class="batch_delete" v-if="multipleSelection.length > 0 && hasPermission('admin/protype', 'delete')">
                    <el-button type="danger" @click="batchDelete">批量删除</el-button>
                </div>
                <el-dialog title="名下代理商" :visible.sync="dialogTableVisible" :modal-append-to-body="false" class="agent-dialog">
                    <el-table :data="gridData">
                        <el-table-column
                                type="index"
                                :index="indexMethod">
                        </el-table-column>
                        <el-table-column property="name" label="客户名称"></el-table-column>
                        <el-table-column property="contact" label="联系人"></el-table-column>
                        <el-table-column property="phone" label="联系电话"></el-table-column>
                        <el-table-column property="email" label="电子邮箱"></el-table-column>
                    </el-table>
                    <el-pagination
                            @current-change="pageChange"
                            :current-page.sync="agentCurrentPage"
                            :page-size="perPage"
                            layout="total, prev, pager, next"
                            :total="agentTotal">
                    </el-pagination>
                </el-dialog>
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
        Message,
        Loading,
        MessageBox,
        Dialog,
        Popover
    } from 'element-ui'
    import PageTitle from '../../frame/PageTitle.vue'
    import { checkToken }  from '../../ajax';
    import { aesencode, aesdecode }  from '../../utils';

    Vue.use(Table);
    Vue.use(TableColumn);
    Vue.use(Pagination);
    Vue.use(Input);
    Vue.use(Button);
    Vue.use(Loading);
    Vue.use(Dialog);
    Vue.use(Popover);
    Vue.prototype.$confirm = MessageBox.confirm;
    Vue.prototype.$message = Message;

    export default {
        computed: {
            ...mapState(["domainUrl", 'baseURL', 'typeOptions', 'brandOptions']),
        },
        data(){
            return {
                currentPage: 1,
                perPage: 10,
                total: 1,
                status: 'all',
                loading: false,
                tableData: [],
                dialogTableVisible: false,
                agentCurrentPage: 1,
                agentTotal: 1,
                mid: '',
                multipleSelection: [],
                gridData: []
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
            lists(){
                var that = this;
                checkToken(function () {
                    that.loading = true;
                    that.axios.get('/maintenance/lists', {
                        params: { //请求参数
                            page: that.currentPage,
                            num: that.perPage,
                            uid: that.searchUid,
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
            indexMethod(index){
                return index + 1;
            },
            search(){
                this.currentPage = 1;
                this.total = 1;
                this.tableData = [];
                this.lists()
            },
            showAgents(index, row){
                this.mid = row.id;
                this.agents();
            },
            pageChange(val){
                this.agentCurrentPage = val;
                this.agents();
            },
            agents(){
                var that = this;
                checkToken(function () {
                    that.loading = true;
                    that.axios.get('/maintenance/agents', {
                        params: { //请求参数
                            page: that.agentCurrentPage,
                            num: that.perPage,
                            mid: that.mid,
                            privilege: encodeURIComponent(aesencode('read'))
                        }
                    })
                    .then(function (response) {
                        if (response.data.code == 0) {
                            that.gridData = response.data.data.data;
                            that.agentTotal = response.data.data.total;
                            if(that.agentTotal > 0){
                                that.dialogTableVisible = true;
                            }
                            else{
                                Message.warning({
                                    message: '名下还没有代理商'
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
                        that.axios.post('/maintenance/batchdelete', {
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
    .status.wait{
        color: #e6a23c;
    }
    .status.agree{
        color: green;
    }
    .status.disagree{
        color: red;
    }
    .agent-dialog .el-pagination{
        border: none;
    }
    .face_img{
        height: 60px;
    }
</style>