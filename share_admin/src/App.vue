<template>
    <div id="app">
        <router-view></router-view>
    </div>
</template>
<script>

</script>
<style>
    body, html{
        width:100%;
        height:100%;
        background: #f4f4f4;
        overflow: hidden;
    }
    #app{
        width:100%;
        height:100%;
        font-size: 0.14rem;
    }
    .el-table td, .el-table th{
        padding: 8px 0px;
    }
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
    .description .cell{
        overflow : hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        max-height: 60px;
        line-height: 20px;
        -webkit-box-orient: vertical;
    }
    .wrap-hundred .cell{
        overflow : hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        width: 100px;
    }
    .clear{
        clear: both;
    }
    .pop_img{
        width: 400px;
    }
    .txt-center{
        text-align: center;
    }
    th.txt-center{
        text-align: center;
    }
    .lookWrapper{
        position: relative;
    }
    .batch_delete{
        position: absolute;
        left: 20px;
        bottom: 20px;
        height: 50px;
        line-height: 50px;
        min-width:120px;
    }

    /*scrollbar begin*/
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    ::-webkit-scrollbar-track-piece {
        background-color: transparent;
        -webkit-border-radius: 2px;
    }
    ::-webkit-scrollbar-thumb:vertical {
        height: 6px;
        background-color: #7F8A8E;
        -webkit-border-radius: 2px;
        -webkit-transition: .8s all;
    }
    ::-webkit-scrollbar-thumb:horizontal {
        width: 6px;
        background-color: #7F8A8E;
        -webkit-border-radius: 2px;
        -webkit-transition: .8s all;
    }
    ::-webkit-scrollbar-thumb:vertical:hover {
        height: 6px;
        background-color: #677377;
        -webkit-border-radius: 2px;
        -webkit-transition: .8s all;
    }
    ::-webkit-scrollbar-thumb:horizontal:hover {
        height: 6px;
        background-color: #677377;
        -webkit-border-radius: 2px;
        -webkit-transition: .8s all;
    }

    html{
        scrollbar-face-color:#f6f6f6;
        scrollbar-highlight-color:#fff;
        scrollbar-shadow-color:#eeeeee;
        scrollbar-3dlight-color:#eeeeee;
        scrollbar-arrow-color:#000;
        scrollbar-track-color:#fff;
        scrollbar-darkshadow-color:#fff;
    }
    scrollbar {
        width: 6px;
    }
    /*scrollbar end*/
</style>