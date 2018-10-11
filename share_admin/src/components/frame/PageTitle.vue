<template>
    <div class="pageTitle">{{title}}</div>
</template>
<script>
    export default {
        props: ['title']
    }
</script>
<style>
    .pageTitle{
        background-color: #f6f8f8;
        height: 80px;
        line-height: 80px;
        padding-left: 30px;
        font-size: 28px;
        border-bottom: 1px solid #dee5e7;
    }
</style>