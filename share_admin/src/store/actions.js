import * as types from './types.js'
export default {
    hideLoading: ({ commit, state })=>{
        commit( types.HIDELOADING );
    },
    showLoading: ({ commit, state })=>{
        commit( types.SHOWLOADING );
    },
    login: ({ commit, state }, param)=>{
        commit( types.LOGIN, param );
    },
    logout: ({ commit, state })=>{
        commit( types.LOGOUT );
    },
    regist: ({ commit, state })=>{
        commit( types.REGIST );
    },
    increaseCart: ({ commit, state }, param)=>{
        commit( types.INCREASE_CART, param );
    },
    decreaseCart: ({ commit, state }, param)=>{
        commit( types.DECREASE_CART, param );
    },
    collapse: ({ commit, state })=>{
        commit( types.COLLAPSE );
    },
    activeIndex: ({ commit, state }, param)=>{
        commit( types.ACTIVEINDEX, param );
    },
    userInfo: ({ commit, state }, param)=>{
        commit( types.USERINFO, param );
    },
    refresh: ({ commit, state }, param)=>{
        commit( types.REFRESH, param );
    },
    permissions: ({ commit, state }, param)=>{
        commit( types.PERMISSIONS, param );
    },
}
