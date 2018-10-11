<template>
    <div>
        <el-cascader
                :options="cityOptions"
                v-model="city"
                @change="changeProvince"
                change-on-select
        >
        </el-cascader>
    </div>
</template>
<script>
    /**
     *  省 市 区/县城  三联动选择器
     */
    import cityOptions  from './data.js'
    import Vue from 'vue'
    import {
        Cascader
    } from 'element-ui';

    Vue.use(Cascader);

    export default {
        props: {
            value: {
                type: Array,
                default: []
            }
        },
        data() {
            return {
                city: this.value,
                cityOptions: cityOptions,
            }
        },
        methods:{
            changeProvince(v){
                //console.log(v)
            },
            getVal(){
                return this.city;
            }
        }
    }
</script>
<style scoped>
    .el-cascader{
        display: block;
    }
</style>