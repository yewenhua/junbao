<template>
    <div>
        <PageTitle title="提现审核"></PageTitle>
        <div class="pageWrapper">
            <div class="lookWrapper">
                <div class="searchWrapper">
                    <el-select v-model="searchUid" placeholder="代理商" @change="chgUid" size="medium" style="width: 130px;">
                        <el-option key="全部用户" label="全部用户" value="all" v-if="usertype=='admin'"></el-option>
                        <el-option
                                v-for="item in agents"
                                :key="item.id"
                                :label="item.name"
                                :value="item.id">
                        </el-option>
                    </el-select>
                    <el-date-picker
                            v-model="dateTime"
                            type="daterange"
                            size="medium"
                            align="left"
                            @change="dateRange"
                            format="yyyy年MM月dd日"
                            value-format="yyyy-MM-dd"
                            unlink-panels
                            range-separator="至"
                            start-placeholder="开始日期"
                            end-placeholder="结束日期"
                            :picker-options="pickerOptions">
                    </el-date-picker>
                    <el-button type="primary" size="medium" class="addBtn" icon="el-icon-tickets" @click="excel" v-if="1==2 && hasPermission('admin/checkcash', 'read')">导出Excel</el-button>
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
                        style="width: 100%">
                    <el-table-column
                            type="index"
                            :index="indexMethod">
                    </el-table-column>
                    <el-table-column
                            prop="name"
                            width="100"
                            label="代理商">
                    </el-table-column>
                    <el-table-column
                            prop="money"
                            label="金额(元)">
                    </el-table-column>
                    <el-table-column
                            prop="status"
                            label="状态">
                        <template slot-scope="scope">
                            <div class="status" :class="scope.row.status == 0 ? 'wait' : scope.row.status == 2 ? 'agree' : 'disagree'">{{scope.row.status == 0 ? '待审核' : scope.row.status == 2 ? '已通过' : '不通过'}}</div>
                        </template>
                    </el-table-column>
                    <el-table-column
                            prop="created_at"
                            width="180"
                            label="申请时间">
                        <template slot-scope="scope">
                            <div>{{scope.row.created_at ? scope.row.created_at.substring(0, 16) : ''}}</div>
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" width="200" v-if="hasPermission('admin/checkcash', 'update') || hasPermission('admin/checkcash', 'delete')">
                        <template slot-scope="scope">
                            <el-button
                                    size="mini"
                                    type="success"
                                    v-if="scope.row.status == 0 && hasPermission('admin/checkcash', 'update')"
                                    @click="agree(scope.$index, scope.row)">通过</el-button>
                            <el-button
                                    size="mini"
                                    type="danger"
                                    v-if="scope.row.status == 0 && hasPermission('admin/checkcash', 'delete')"
                                    @click="disagree(scope.$index, scope.row)">不通过</el-button>
                            <div  v-if="scope.row.status != 0">已审核</div>
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
        Select,
        Option,
        Switch,
        DatePicker
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
    Vue.use(Select);
    Vue.use(Option);
    Vue.use(Switch);
    Vue.use(DatePicker);
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
                searchUid: 'all',
                status: 'all',
                loading: false,
                tableData: [],
                pickerOptions: {
                    shortcuts: [{
                        text: '最近一周',
                        onClick(picker) {
                            const end = new Date();
                            const start = new Date();
                            start.setTime(start.getTime() - 3600 * 1000 * 24 * 7);
                            picker.$emit('pick', [start, end]);
                        }
                    }, {
                        text: '最近一个月',
                        onClick(picker) {
                            const end = new Date();
                            const start = new Date();
                            start.setTime(start.getTime() - 3600 * 1000 * 24 * 30);
                            picker.$emit('pick', [start, end]);
                        }
                    }, {
                        text: '最近三个月',
                        onClick(picker) {
                            const end = new Date();
                            const start = new Date();
                            start.setTime(start.getTime() - 3600 * 1000 * 24 * 90);
                            picker.$emit('pick', [start, end]);
                        }
                    }]
                },
                dateTime: '',
                agents: [],
                usertype: 'agent'
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
                    that.axios.get('/cash/cashlog', {
                        params: { //请求参数
                            page: that.currentPage,
                            num: that.perPage,
                            uid: that.searchUid,
                            status: that.status,
                            start: that.dateTime ? that.dateTime[0] : '',
                            end: that.dateTime ? that.dateTime[1] : '',
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
            chgUid(){
                this.search();
            },
            dateRange(){
                this.search();
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
            excel(){

            },
            agree(index, row){
                var that = this;
                that.$confirm('确定要通过吗?', '提示', {
                    confirmButtonText: '确定',
                    cancelButtonText: '取消',
                    type: 'warning'
                }).then(() => {
                    checkToken(function () {
                        that.loading = true;
                        that.axios.post('/cash/checkcash', {
                            status: 'agree',
                            id: row.id,
                            uid: row.uid,
                            privilege: encodeURIComponent(aesencode('update'))
                        })
                        .then(function (response) {
                            that.loading = false;
                            Message.success({
                                message: '提交成功'
                            });
                            that.lists();
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
            disagree(index, row){
                var that = this;
                that.$confirm('确定要不通过吗?', '提示', {
                    confirmButtonText: '确定',
                    cancelButtonText: '取消',
                    type: 'warning'
                }).then(() => {
                    checkToken(function () {
                        that.loading = true;
                        that.axios.post('/cash/checkcash', {
                            status: 'disagree',
                            id: row.id,
                            uid: row.uid,
                            privilege: encodeURIComponent(aesencode('update'))
                        })
                        .then(function (response) {
                            that.loading = false;
                            Message.success({
                                message: '提交成功'
                            });
                            that.lists();
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