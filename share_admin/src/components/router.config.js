import Vue from 'vue'
import VueRouter from 'vue-router'
import store from '../store/'

import Frame from './frame/Frame.vue'
import Privilege from './privilege/Privilege.vue'
import Role from './privilege/Role.vue'
import User from './privilege/User.vue'
import Login from './personal/Login.vue'
import Personal from './personal/Personal.vue'
import MyAgents from './personal/MyAgents.vue'

import Userinfo from './page/Userinfo.vue'
import Chgpwd from './page/Chgpwd.vue'

import Protype from './page/Maintenance/Protype.vue'
import Probrand from './page/Maintenance/Probrand.vue'
import Pricetpl from './page/Maintenance/Pricetpl.vue'
import Discount from './page/Maintenance/Discount.vue'
import Maintenance from './page/Maintenance/Maintenance.vue'

import Welcome from './page/Welcome.vue'
import PwdDevice from './page/Device/PwdDevice.vue'
import DeviceConsume from './page/Finance/DeviceConsume.vue'
import DeviceStatistic from './page/Finance/DeviceStatistic.vue'
import CheckCash from './page/Finance/CheckCash.vue'
import CashLog from './page/Finance/CashLog.vue'

Vue.use(VueRouter)

const routes = [
    {
        path: '/admin',
        component: Frame,
        children: [
            {
                path: 'privilege',
                component: Privilege,
                meta: {
                    requireAuth: true,
                    agent: 'pc'
                }
            },
            {
                path: 'role',
                component: Role,
                meta: {
                    requireAuth: true,
                    agent: 'pc'
                }
            },
            {
                path: 'user',
                component: User,
                meta: {
                    requireAuth: true,
                    agent: 'pc'
                }
            },
            {
                path: 'info',
                component: Userinfo,
                meta: {
                    requireAuth: true,
                    agent: 'pc'
                }
            },
            {
                path: 'chgpwd',
                component: Chgpwd,
                meta: {
                    requireAuth: true,
                    agent: 'pc'
                }
            },
            {
                path: 'myagents',
                component: MyAgents,
                meta: {
                    requireAuth: true,
                    agent: 'pc'
                }
            },
            {
                path: 'protype',
                component: Protype,
                meta: {
                    requireAuth: true,
                    agent: 'pc'
                }
            },
            {
                path: 'probrand',
                component: Probrand,
                meta: {
                    requireAuth: true,
                    agent: 'pc'
                }
            },
            {
                path: 'welcome',
                component: Welcome,
                meta: {
                    requireAuth: true,
                    agent: 'pc'
                }
            },
            {
                path: 'pwddevice',
                component: PwdDevice,
                meta: {
                    requireAuth: true,
                    agent: 'pc'
                }
            },
            {
                path: 'deviceconsume',
                component: DeviceConsume,
                meta: {
                    requireAuth: true,
                    agent: 'pc'
                }
            },
            {
                path: 'devicestatistic',
                component: DeviceStatistic,
                meta: {
                    requireAuth: true,
                    agent: 'pc'
                }
            },
            {
                path: 'discount',
                component: Discount,
                meta: {
                    requireAuth: true,
                    agent: 'pc'
                }
            },
            {
                path: 'checkcash',
                component: CheckCash,
                meta: {
                    requireAuth: true,
                    agent: 'pc'
                }
            },
            {
                path: 'cashlog',
                component: CashLog,
                meta: {
                    requireAuth: true,
                    agent: 'pc'
                }
            },
            {
                path: 'pricetpl',
                component: Pricetpl,
                meta: {
                    requireAuth: true,
                    agent: 'pc'
                }
            },
            {
                path: 'maintenance',
                component: Maintenance,
                meta: {
                    requireAuth: true,
                    agent: 'pc'
                }
            },
        ]
    },
    {
        path: '/personal',
        component: Personal,
        children: [
            {
                path: 'login',
                component: Login,
                meta: {
                    requireAuth: false,
                    agent: 'pc'
                }
            },
        ]
    },
    {
        path: '*',
        redirect: '/admin/welcome'
    }
];

/*
 * 新建一个方法  当new Router时，实例就包含goBack方法
 * 可用this.$router.goBack()调用
 */
VueRouter.prototype.goBack = function () {
    this.isBack = true;
    this.go(-1);
}

const router = new VueRouter({
    scrollBehavior: () => ({ y: 0 }), // 滚动条滚动的行为，不加这个默认就会记忆原来滚动条的位置
    routes
});

router.beforeEach((to, from, next) => {
    store.dispatch('showLoading');
    if (to.matched.some(r => r.meta.requireAuth)) {
        if (store.state.token) {
            next();
        }
        else {
            next({
                path: '/personal/login',
                query: {redirect: to.fullPath}
            })
        }
    }
    else {
        next();
    }
});

router.afterEach(function (to) {
    store.dispatch('hideLoading')
});

export default router;


