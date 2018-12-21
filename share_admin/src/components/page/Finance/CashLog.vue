<template>
    <div>
        <PageTitle title="提现记录"></PageTitle>
        <div class="pageWrapper">
            <div class="lookWrapper">
                <div class="searchWrapper">
                    <el-select filterable v-if="usertype=='admin'" v-model="searchUid" placeholder="代理商" @change="chgUid" size="medium" style="width: 130px;">
                        <el-option key="全部用户" label="全部用户" value="all"></el-option>
                        <el-option
                                v-for="item in agents"
                                :key="item.id"
                                :label="item.name"
                                :value="item.id">
                        </el-option>
                    </el-select>
                    <div class="rest-money" v-loading="moneying" style="margin-left: 10px;"><span v-if="usertype=='admin'">营业额： <span class="value">{{sale_money}}</span>元，</span><span v-if="searchUid != 'all'">可用余额： <span class="value">{{rest_money}}</span>元，</span>冻结金额： <span class="freeze">{{freeze_money}}</span>元，已提现金额： <span class="take">{{take_money}}</span>元</div>
                    <el-button type="primary" size="medium" class="takeCashBtn" :disabled="rest_money<=0" @click="takecash" v-if="freeze_money <=0 && rest_money > 0 && hasPermission('admin/cashlog', 'update') && usertype=='agent'">提现</el-button>
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
                            label="申请人">
                    </el-table-column>
                    <el-table-column
                            prop="money"
                            label="金额（元）">
                        <template slot-scope="scope">
                            <div>{{scope.row.money}}</div>
                        </template>
                    </el-table-column>
                    <el-table-column
                            prop="status"
                            label="审核状态">
                        <template slot-scope="scope">
                            <div class="status" :class="scope.row.status == 0 ? 'wait' : scope.row.status == 2 ? 'agree' : 'disagree'">{{scope.row.status == 0 ? '待审核' : scope.row.status == 2 ? '已通过' : '不通过'}}</div>
                        </template>
                    </el-table-column>
                    <el-table-column
                            prop="created_at"
                            label="申请时间">
                        <template slot-scope="scope">
                            <div>{{scope.row.created_at ? scope.row.created_at.substring(0, 16) : ''}}</div>
                        </template>
                    </el-table-column>
                </el-table>
                <el-pagination
                        v-if="total > 0"
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
        Button,
        Message,
        Loading,
        MessageBox,
    } from 'element-ui'
    import PageTitle from '../../frame/PageTitle.vue'
    import { checkToken }  from '../../ajax';
    import { aesencode, aesdecode }  from '../../utils';

    Vue.use(Table);
    Vue.use(TableColumn);
    Vue.use(Pagination);
    Vue.use(Button);
    Vue.use(Loading);
    Vue.prototype.$confirm = MessageBox.confirm;
    Vue.prototype.$message = Message;

    export default {
        computed: {
            ...mapState(["domainUrl", 'baseURL']),
        },
        data(){
            return {
                currentPage: 1,
                perPage: 10,
                total: 1,
                status: 'all',
                loading: false,
                moneying: false,
                searchUid: 'all',
                tableData: [],
                sale_money: 0,
                rest_money: 0,
                freeze_money: 0,
                take_money: 0,
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
                            status: that.status,
                            uid: that.searchUid,
                            privilege: encodeURIComponent(aesencode('read'))
                        }
                    })
                    .then(function (response) {
                        that.loading = false;
                        if (response.data.code == 0) {
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
            indexMethod(index){
                return index + 1;
            },
            getMoney(){
                var that = this;
                checkToken(function () {
                    that.moneying = true;
                    that.axios.get('/cash/money', {
                        params: { //请求参数
                            uid: that.searchUid,
                            privilege: encodeURIComponent(aesencode('read'))
                        }
                    })
                    .then(function (response) {
                        if (response.data.code == 0) {
                            that.sale_money = response.data.data.sale_money;
                            that.rest_money = response.data.data.rest_money;
                            that.freeze_money = response.data.data.freeze_money;
                            that.take_money = response.data.data.take_money;
                        }
                        that.moneying = false;
                    })
                    .catch(function (error) {
                        Message.error({
                            message: '未知错误'
                        });
                        that.moneying = false;
                    });
                });
            },
            takecash() {
                var that = this;
                that.$confirm('确定要提现吗?', '提示', {
                    confirmButtonText: '确定',
                    cancelButtonText: '取消',
                    type: 'warning'
                }).then(() => {
                    checkToken(function () {
                        that.loading = true;
                        that.axios.post('/cash/takecash', {
                            money: that.rest_money,
                            privilege: encodeURIComponent(aesencode('update'))
                        })
                        .then(function (response) {
                            that.loading = false;
                            that.freeze_money = that.rest_money;
                            that.rest_money = 0;
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
                            that.getMoney();
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
            chgUid(){
                this.lists();
                this.getMoney();
            },
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
    .rest-money{
        font-size: 18px;
        display: inline-block;
    }
    .rest-money .value{
        color:#f40;
    }
    .rest-money .freeze{
        color:red;
    }
    .rest-money .take{
        color:green;
    }
    .takeCashBtn{
        float: right;
    }
</style>