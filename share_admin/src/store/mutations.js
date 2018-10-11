import * as types from './types.js'

export default {
    [types.HIDELOADING]: ( state )=>{
        state.loading = false;
    },
    [types.SHOWLOADING]: ( state )=>{
        state.loading = true;
    },
    [types.LOGIN]: ( state, param )=>{
        var expire = {
            value: param.token,
            expire_time: (new Date()).getTime() + 3600 * 1000,
            refresh_time: (new Date()).getTime() + 3600 * 1000 * 24
        }
        sessionStorage.setItem('token', JSON.stringify(expire));
        sessionStorage.setItem('permissions', JSON.stringify(param.permissions));
        sessionStorage.setItem('activeIndex', param.activeIndex);
        sessionStorage.setItem('userInfo', JSON.stringify(param.userInfo));
        sessionStorage.setItem('typeOptions', JSON.stringify(param.typeOptions));
        sessionStorage.setItem('brandOptions', JSON.stringify(param.brandOptions));
        state.token = expire;
        state.permissions = param.permissions;
        state.activeIndex = param.activeIndex;
        state.userInfo = param.userInfo;
        state.typeOptions = param.typeOptions;
        state.brandOptions = param.brandOptions;
    },
    [types.LOGOUT]: ( state )=>{
        sessionStorage.removeItem('token');
        sessionStorage.removeItem('permissions');
        sessionStorage.removeItem('activeIndex');
        sessionStorage.removeItem('userInfo');
        sessionStorage.removeItem('typeOptions');
        sessionStorage.removeItem('brandOptions');
        state.token = '';
        state.permissions = '';
        state.activeIndex = '';
        state.userInfo = '';
        state.typeOptions = [];
        state.brandOptions = [];
    },
    [types.REGIST]: ( state )=>{
        state.token = '9527';
    },
    [types.INCREASE_CART]: ( state, param )=>{
        state.ball = param;
    },
    [types.DECREASE_CART]: ( state, param )=>{

    },
    [types.COLLAPSE]: ( state )=>{
        state.collapse = !state.collapse;
    },
    [types.ACTIVEINDEX]: ( state, param )=>{
        sessionStorage.setItem('activeIndex', param.activeIndex);
        state.activeIndex = param.activeIndex;
    },
    [types.USERINFO]: ( state, param )=>{
        sessionStorage.setItem('userInfo', JSON.stringify(param.userInfo));
        state.userInfo = param.userInfo;
    },
    [types.REFRESH]: ( state, param )=>{
        var expire = {
            value: param.token,
            expire_time: (new Date()).getTime() + 3600 * 1000,
            refresh_time: (new Date()).getTime() + 3600 * 1000 * 24 * 14
        }
        sessionStorage.setItem('token', JSON.stringify(expire));
        state.token = expire;
    },
    [types.PERMISSIONS]: ( state, param )=>{
        sessionStorage.setItem('permissions', JSON.stringify(param.permissions));
        state.permissions = param.permissions;
    },
}
