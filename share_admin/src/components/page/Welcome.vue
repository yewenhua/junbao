<template>
    <div>
        <PageTitle title="消费统计"></PageTitle>
        <div class="pageWrapper" v-loading="loading">
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
                    <el-input placeholder="请输入设备编号" v-model="searchkey" size="medium">
                        <el-button slot="append" size="medium" icon="el-icon-search" @click="search">搜索</el-button>
                    </el-input>
                </div>
            </div>
            <ul class="wel-gragh">
                <li v-show="hasData">
                    <h-chart :id="idFirst" :option="optionColumn" v-if="hasData"></h-chart>
                </li>
            </ul>
        </div>
    </div>
</template>
<script>
    import Vue from 'vue'
    import { mapState } from 'vuex'
    import HChart from '../Chart/HChart.vue';
    import PageTitle from '../frame/PageTitle.vue'
    import {
        Button,
        Input,
        Message,
        Loading,
        MessageBox,
        Select,
        Option,
        DatePicker
    } from 'element-ui'
    import { checkToken }  from '../ajax';
    import { aesencode, aesdecode }  from '../utils';

    Vue.use(Button);
    Vue.use(Input);
    Vue.use(Loading);
    Vue.use(Select);
    Vue.use(Option);
    Vue.use(DatePicker);
    Vue.prototype.$confirm = MessageBox.confirm;
    Vue.prototype.$message = Message;

    export default {
        computed: {
            ...mapState(["domainUrl", 'baseURL', 'typeOptions', 'brandOptions']),
        },
        data() {
            let optionColumn = {
                chart: {
                    type: 'column'
                },
                title: {
                    text: '过去七天消费量趋势',
                },
                xAxis: {
                    categories: [],
                    crosshair: true
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: '消费量 (元)'
                    }
                },
                legend: {
                    align: 'center',
                    x: 30,
                    verticalAlign: 'top',
                    y: 25,
                    floating: true,
                    backgroundColor: 'white',
                    shadow: false
                },
                tooltip: {
                    formatter: function () {
                        return this.x + '<br/>消费总金额：' + this.y + '元';
                    }
                },
                plotOptions: {
                    column: {
                        borderWidth: 0
                    },
                    series: {
                        dataLabels: {
                            enabled: true,
                            formatter: function () {
                                return this.y + "元";
                            }
                        },
                    }
                },
                series: [{
                    name: '消费总金额',
                    data: []
                }],
                credits: {
                    enabled: false     //不显示LOGO
                }
            };
            let optionPie = {
                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                title: {
                    text: '2018年1月浏览器市场份额'
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                            style: {
                                color: 'black'
                            }
                        }
                    }
                },
                series: [{
                    name: 'Brands',
                    colorByPoint: true,
                    data: [{
                        name: 'Chrome',
                        y: 61.41,
                        sliced: true,
                        selected: true
                    }, {
                        name: 'Internet Explorer',
                        y: 11.84
                    }, {
                        name: 'Firefox',
                        y: 10.85
                    }, {
                        name: 'Edge',
                        y: 4.67
                    }, {
                        name: 'Safari',
                        y: 4.18
                    }, {
                        name: 'Sogou Explorer',
                        y: 1.64
                    }, {
                        name: 'Opera',
                        y: 1.6
                    }, {
                        name: 'QQ',
                        y: 1.2
                    }, {
                        name: 'Other',
                        y: 2.61
                    }]
                }]
            };

            return {
                hasData: false,
                idFirst: 'first',
                idPie: 'fouth',
                optionColumn: optionColumn,
                optionPie: optionPie,
                searchkey: '',
                searchUid: 'all',
                searchType: 'all',
                searchBrand: 'all',
                loading: false,
                statistic: [],
                agents: [],
                usertype: 'agent'
            }
        },
        methods:{
            getStatistic(){
                var that = this;
                checkToken(function () {
                    that.loading = true;
                    that.hasData = false;
                    that.axios.get('/cash/statistic', {
                        params: { //请求参数
                            uid: that.searchUid,
                            search: that.searchkey,
                            privilege: encodeURIComponent(aesencode('read'))
                        }
                    })
                    .then(function (response) {
                        if (response.data.code == 0) {
                            that.hasData = true;
                            that.statistic = response.data.data;
                            let categories = [];
                            let data = [];
                            for(let key in that.statistic){
                                categories.push(key);
                                data.push(that.statistic[key]);
                            }
                            that.optionColumn.xAxis.categories = categories;
                            that.optionColumn.series[0].data = data;
                        }
                        that.loading = false;
                    })
                    .catch(function (error) {
                        Message.error({
                            message: '未知错误'
                        });
                        that.loading = false;
                        that.hasData = false;
                    });
                });
            },
            search(){
                this.getStatistic();
            },
            chgType(){
                this.getStatistic();
            },
            chgBrand(){
                this.getStatistic();
            },
            chgUid(){
                this.getStatistic();
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
                            that.getStatistic();
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
        },
        components: {
            HChart,
            PageTitle
        },
        mounted() {
            this.getAgents();
        }
    }
</script>
<style>
    .wel-gragh{
        list-style: none;
        margin: 0;
        padding:0;
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
    }
    .wel-gragh li{
        width: 100%;
        box-sizing: border-box;
        height: 415px;
        overflow: auto;
        -webkit-overflow-scrolling : touch;
        margin-bottom: 15px;
        padding-top: 15px;
        background: white;
    }
    .wel-gragh li:nth-child(odd){
        padding-right: 8px;
    }
    .wel-gragh li:nth-child(even){
        padding-left: 8px;
    }
    #first{
        height: 400px;
    }
    @media (min-width: 1500px){
        .wel-gragh li{
            height: 615px;
        }
        #first{
            height: 600px;
        }
    }

</style>