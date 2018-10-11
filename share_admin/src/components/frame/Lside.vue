<template>
    <div class="lsideWrapper">
        <el-menu
                :defaultActive="activeIndex"
                :uniqueOpened="true"
                :router="true"
                :collapse="collapse"
                class="el-menu-vertical-demo"
                background-color="#1c2b36"
                text-color="#fff"
                active-text-color="#ffd04b"
                @select="handleSelect"
                @open="handleOpen"
                @close="handleClose">
            <el-submenu index="1" v-if="hasPagePermission('admin/maintenance') || hasPagePermission('admin/myagents')">
                <template slot="title">
                    <i class="el-icon-upload maintenance"></i>
                    <span class="mtext">运维管理</span>
                </template>
                <el-menu-item index="/admin/myagents" class="mtext" v-if="hasPagePermission('admin/myagents')">我的客户</el-menu-item>
                <el-menu-item index="/admin/maintenance" class="mtext" v-if="hasPagePermission('admin/maintenance')">市场维护</el-menu-item>
            </el-submenu>
            <el-submenu index="2" v-if="hasPagePermission('admin/pwddevice')">
                <template slot="title">
                    <i class="el-icon-mobile-phone device"></i>
                    <span class="mtext">设备管理</span>
                </template>
                <el-menu-item index="/admin/pwddevice" class="mtext" v-if="hasPagePermission('admin/pwddevice')">密码支付设备</el-menu-item>
            </el-submenu>
            <el-submenu index="3" v-if="hasPagePermission('admin/protype') || hasPagePermission('admin/pricetpl') || hasPagePermission('admin/probrand') || hasPagePermission('admin/discount')">
                <template slot="title">
                    <i class="el-icon-menu info"></i>
                    <span class="mtext">基础信息运维</span>
                </template>
                <el-menu-item index="/admin/protype" class="mtext" v-if="hasPagePermission('admin/protype')">产品类型</el-menu-item>
                <el-menu-item index="/admin/probrand" class="mtext" v-if="hasPagePermission('admin/probrand')">产品品牌</el-menu-item>
                <el-menu-item index="/admin/pricetpl" class="mtext" v-if="hasPagePermission('admin/pricetpl')">价格模板</el-menu-item>
                <el-menu-item index="/admin/discount" class="mtext" v-if="hasPagePermission('admin/discount')">分成比列</el-menu-item>
            </el-submenu>
            <el-submenu index="4" v-if="hasPagePermission('admin/takecash') || hasPagePermission('admin/cashlog')">
                <template slot="title">
                    <i class="el-icon-news finance"></i>
                    <span class="mtext">财务管理</span>
                </template>
                <el-menu-item index="/admin/checkcash" class="mtext" v-if="hasPagePermission('admin/checkcash')">提现审核</el-menu-item>
                <el-menu-item index="/admin/cashlog" class="mtext" v-if="hasPagePermission('admin/cashlog')">提现记录</el-menu-item>
            </el-submenu>
            <el-submenu index="5" v-if="hasPagePermission('admin/deviceconsume') || hasPagePermission('admin/devicestatistic') || hasPagePermission('admin/welcome')">
                <template slot="title">
                    <i class="el-icon-tickets statistic"></i>
                    <span class="mtext">经营对账</span>
                </template>
                <el-menu-item index="/admin/welcome" class="mtext" v-if="hasPagePermission('admin/welcome')">消费图表统计</el-menu-item>
                <el-menu-item index="/admin/deviceconsume" class="mtext" v-if="hasPagePermission('admin/deviceconsume')">设备消费流水</el-menu-item>
                <el-menu-item index="/admin/devicestatistic" class="mtext" v-if="hasPagePermission('admin/devicestatistic')">设备消费统计</el-menu-item>
            </el-submenu>
            <el-submenu index="6" popper-class="mypop" v-if="hasPagePermission('admin/user') || hasPagePermission('admin/role') || hasPagePermission('admin/privilege')">
                <template slot="title">
                    <i class="el-icon-star-on system"></i>
                    <span class="mtext">用户中心</span>
                </template>
                <el-menu-item index="/admin/user" class="mtext" v-if="hasPagePermission('admin/user')">用户管理</el-menu-item>
                <el-menu-item index="/admin/role" class="mtext" v-if="hasPagePermission('admin/role')">角色管理</el-menu-item>
                <el-menu-item index="/admin/privilege" class="mtext" v-if="hasPagePermission('admin/privilege')">权限管理</el-menu-item>
            </el-submenu>
            <el-submenu index="7" v-if="hasPagePermission('admin/info') || hasPagePermission('admin/chgpwd')">
                <template slot="title">
                    <i class="el-icon-setting my"></i>
                    <span class="mtext">系统管理</span>
                </template>
                <el-menu-item index="/admin/info" class="mtext" v-if="hasPagePermission('admin/info')">个人信息</el-menu-item>
                <el-menu-item index="/admin/chgpwd" class="mtext" v-if="hasPagePermission('admin/chgpwd')">修改密码</el-menu-item>
            </el-submenu>
        </el-menu>
    </div>
</template>
<script>
    import Vue from 'vue'
    import {
        Row,
        Col,
        Menu,
        Submenu,
        MenuItem,
        MenuItemGroup,
    } from 'element-ui'
    import { mapState } from 'vuex'

    Vue.use(Row);
    Vue.use(Col);
    Vue.use(Menu);
    Vue.use(Submenu);
    Vue.use(MenuItem);
    Vue.use(MenuItemGroup);

    export default {
        computed: {
            ...mapState([
                'collapse',
                'activeIndex',
                'permissions'
            ])
        },
        data(){
            return {

            }
        },
        methods:{
            handleOpen(key, keyPath) {

            },
            handleClose(key, keyPath) {

            },
            handleSelect(key, keyPath){
                this.$store.dispatch('activeIndex', {
                    activeIndex: key
                });
            },
            hasPagePermission(url){
                var flag = false;
                for(var i=0; i<this.permissions.length; i++){
                    if(this.permissions[i].desc == url){
                        flag = true;
                        break;
                    }
                }

                return flag;
            }
        }
    }
</script>
<style lang="scss">
    .lsideWrapper{
        height: 100%;
    }
    .aside-title{
        color: #fff;
        padding-top: 30px;
        padding-bottom: 30px;
        text-align: left;
        padding-left: 30px;
        font-size: 20px;
    }
    .el-menu{
        border-right: none;
        height: 100%;
    }
    .el-menu-vertical-demo:not(.el-menu--collapse) {
        width: 200px;
        min-height: 400px;
    }
    i.system{
        color: #f05050;
    }
    i.web{
        color: #27c24c;
    }
    i.my{
        color: #23b7e5;
    }
    i.info{
        color: #27c24c;
    }
    i.device{
        color: 	#A020F0;
    }
    i.maintenance{
        color: 	#4876FF;
    }
    i.finance{
        color: #fad733;
    }
    i.statistic{
        color: #C0FF3E;
    }
    .is-opened .el-menu-item{
        background: red;
    }
</style>