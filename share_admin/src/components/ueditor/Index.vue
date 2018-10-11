<template>
    <div class="ueditor-outter">
        <script :id="id" type="text/plain"></script>
    </div>
</template>
<script>
    import Vue from 'vue'
    import '../../../UEditor/ueditor.config.js'
    import '../../../UEditor/ueditor.all.min.js'
    import '../../../UEditor/lang/zh-cn/zh-cn.js'
    import '../../../UEditor/ueditor.parse.min.js'

    export default {
        props: {
            id: {
                type: String,
                default: ''
            },
            config: {
                type: Object,
                default: null
            },
            value: {
                type: String,
                default: ''
            }
        },
        data(){
            return {
                editor: null
            }
        },
        mounted() {
            let that = this;
            that.editor = UE.getEditor(that.id, that.config);
            that.editor.addListener("ready", function () {
                that.editor.setContent(that.value); // 确保UE加载完成后，放入内容。
            });
        },
        destroyed() {
            this.editor.destroy();
        },
        methods:{
            getUEContent: function(){
                return this.editor.getContent();
            },
            setUEContent: function(value){
                this.editor.setContent(value);
            }
        }
    }
</script>
<style>
    .fadeup-leave .ueditor-outter{
        opacity: 0;
    }
    .fadeup-leave-active .ueditor-outter{
        opacity: 0;
    }
    .fadeup-leave-to .ueditor-outter{
        opacity: 0;
    }
    .edui-default .edui-editor-bottomContainer{
        display: none;
    }
</style>