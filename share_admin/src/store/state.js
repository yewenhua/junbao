export default {
    loading: false,
    userInfo: sessionStorage.getItem('userInfo') ? JSON.parse(sessionStorage.getItem('userInfo')) : {name: '', desc: '', email: ''},
    token: sessionStorage.getItem('token') ? JSON.parse(sessionStorage.getItem('token')) : '',
    permissions: sessionStorage.getItem('permissions') ? JSON.parse(sessionStorage.getItem('permissions')) : [],
    ball: {
        left: 0,
        bottom: 0
    },
    sitename: '骏宝闪充',
    baseURL: 'http://wx.junbao518.com/api/',
    domainUrl: 'http://wx.junbao518.com',
    collapse: false,
    activeIndex: sessionStorage.getItem('activeIndex') ? sessionStorage.getItem('activeIndex') : '/admin/user',
    typeOptions: sessionStorage.getItem('typeOptions') ? JSON.parse(sessionStorage.getItem('typeOptions')) : [],
    brandOptions: sessionStorage.getItem('brandOptions') ? JSON.parse(sessionStorage.getItem('brandOptions')) : [],
    priceOptions: [
        {
            value: 0,
            label: '0.5小时'
        }, {
            value: 1,
            label: '1小时'
        }, {
            value: 2,
            label: '2小时'
        }, {
            value: 3,
            label: '3小时'
        }, {
            value: 4,
            label: '4小时'
        }, {
            value: 5,
            label: '5小时'
        }, {
            value: 6,
            label: '8小时'
        }, {
            value: 7,
            label: '12小时'
        }, {
            value: 8,
            label: '16小时'
        }, {
            value: 9,
            label: '25小时'
        }
    ]
}