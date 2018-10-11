import Vue from 'vue'
import App from './App.vue'
import router from './components/router.config.js'
import Loading from './components/loading/index.js'
import store from './store/index.js'
import axios from './components/http'

Vue.use(Loading);

// 将axios挂载到prototype上，在组件中可以直接使用this.axios访问
Vue.prototype.axios = axios;

//全局函数，判断页面操作是否有权限方法
Vue.prototype.hasPermission = (url, type)=>{
    var permissions = store.state.permissions;
    var flag = false;
    for(var i=0; i<permissions.length; i++){
        var key = type + '_permission';
        if(permissions[i].desc == url && permissions[i].pivot[key] == 1){
            flag = true;
            break;
        }
    }

    return flag;
}

Vue.config.devtools = true;
new Vue({
  el: '#app',
  router,
  store,
  render: h => h(App)
})
