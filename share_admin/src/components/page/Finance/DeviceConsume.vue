<template>
    <div>
        <PageTitle title="设备消费流水"></PageTitle>
        <div class="pageWrapper">
            <div class="lookWrapper">
                <div class="searchWrapper">
                    <el-select filterable v-model="searchUid" placeholder="代理商" @change="chgUid" size="medium" style="width: 130px;">
                        <el-option key="全部用户" label="全部用户" value="all" v-if="usertype=='admin'"></el-option>
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
                        <el-option key="全部品牌" label="全部品牌" value="all"></el-option>
                        <el-option
                                v-for="item in brandOptions"
                                :key="item.name"
                                :label="item.name"
                                :value="item.name">
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
                    <el-input placeholder="请输入设备编号" v-model="searchkey" size="medium">
                        <el-button slot="append" size="medium" icon="el-icon-search" @click="search">搜索</el-button>
                    </el-input>
                    <el-button type="primary" size="medium" class="addBtn" icon="el-icon-tickets" @click="excel" v-if="hasPermission('admin/deviceconsume', 'read')">导出Excel</el-button>
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
                            prop="username"
                            label="代理商">
                    </el-table-column>
                    <el-table-column
                            prop="type"
                            label="设备类型"
                            width="100">
                    </el-table-column>
                    <el-table-column
                            prop="brand"
                            label="设备品牌"
                            width="100">
                    </el-table-column>
                    <el-table-column
                            prop="sn"
                            label="设备编号">
                    </el-table-column>
                    <el-table-column
                            prop="money"
                            width="80"
                            label="金额(元)">
                    </el-table-column>
                    <el-table-column
                            prop="orderid"
                            label="订单号">
                    </el-table-column>
                    <el-table-column
                            prop="pay_no"
                            label="微信流水号">
                    </el-table-column>
                    <el-table-column
                            prop="tpl_name"
                            label="消费科目"
                            width="100">
                    </el-table-column>
                    <el-table-column
                            prop="cash_status"
                            label="结算状态"
                            width="80">
                        <template slot-scope="scope">
                            <div class="status" :class="scope.row.cash_status == 0 ? 'wait' : scope.row.cash_status == 2 ? 'agree' : 'freeze'">{{scope.row.cash_status == 1 ? '已冻结' : scope.row.cash_status == 2 ? '已结算' : '未结算'}}</div>
                        </template>
                    </el-table-column>
                    <el-table-column
                            prop="created_at"
                            label="创建时间">
                        <template slot-scope="scope">
                            <div>{{scope.row.pay_time ? scope.row.pay_time.substring(0, 16) : ''}}</div>
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
            ...mapState(["domainUrl", 'baseURL', 'typeOptions', 'brandOptions', 'token']),
        },
        data(){
            return {
                currentPage: 1,
                perPage: 10,
                total: 1,
                searchkey: '',
                searchUid: 'all',
                searchType: 'all',
                searchBrand: 'all',
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
                    that.axios.get('/devices/consume', {
                        params: { //请求参数
                            page: that.currentPage,
                            num: that.perPage,
                            search: that.searchkey,
                            type: that.searchType,
                            brand: that.searchBrand,
                            uid: that.searchUid,
                            start: that.dateTime ? that.dateTime[0] : '',
                            end: that.dateTime ? that.dateTime[1] : '',
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
                        that.loading = false;
                        if (response.data.code == 0) {
                            that.agents = response.data.data.list;
                            that.usertype = response.data.data.type;
                            if(that.usertype == 'agent'){
                                that.agents[0].name = '我运营的设备';
                                that.searchUid = that.agents[0].id;
                            }
                            that.lists();
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
            excel(){
                let url = this.baseURL + "devices/consumeexcel?search=" + this.searchkey + '&type=' + this.searchType + '&brand=' + this.searchBrand + '&uid=' + this.searchUid + '&start=' + (this.dateTime ? this.dateTime[0] : '') + '&end=' + (this.dateTime ? this.dateTime[1] : '') + '&token=' + this.token.value + '&path=' + encodeURIComponent(aesencode('admin/deviceconsume')) + '&privilege=' + encodeURIComponent(aesencode('read'));
                window.open(url);
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
    .status.freeze{
        color: red;
    }
</style>