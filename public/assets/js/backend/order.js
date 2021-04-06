define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/index' + location.search,
                    // add_url: 'order/add',
                    edit_url: 'order/edit',
                    del_url: 'order/del',
                    /*multi_url: 'order/multi',
                    import_url: 'order/import',*/
                    table: 'order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'order_no', title: __('订单号')},
                        {field: 'user.nickname', title: __('用户昵称')},
                        {field: 'goods_name', title: __('Goods_name'), operate: 'LIKE'},
                        // {field: 'goods_cover', title: __('Goods_cover'), operate: 'LIKE'},
                        {field: 'goods_money', title: __('商品单价')},
                        {field: 'goods_num', title: __('Goods_num')},
                        {field: 'money', title: __('订单金额'), operate:'BETWEEN'},
//                        {
//                            field: 'pay_type',
//                            title: __('支付方式'),
//                            formatter: function(value){
//                                if(value == 'alipay' || value == 'codepay_alipay'){
//                                    return `<span class="label label-success">支付宝</span>`;
//                                }else if(value == 'wxpay' || value == 'codepay_wxpay'){
//                                    return `<span class="label label-info">微信</span>`;
//                                }else if(value == 'qqpay' || value == 'codepay_qqpay'){
//                                    return `<span class="label label-warning">QQ</span>`;
//                                }else{
//                                    return `<span class="label label-danger">未知</span>`;
//                                }
//
//                            }
//                        },
                        {
                            field: 'status',
                            title: __('订单状态'),
                            formatter: function(value){
                                if(value == 'yiguoqi'){
                                    return '<span class="label label-default">已过期</span>';
                                }else if(value == 'weizhifu'){
                                    return '<span class="label label-default">未支付</span>';
                                }else if(value == 'daifahuo'){
                                    return '<span class="label label-danger">待发货</span>';
                                }else if(value == 'yifahuo'){
                                    return '<span class="label label-success">已发货</span>';
                                }else if(value == 'success'){
                                    return '<span class="label label-success">已完成</span>';
                                }else {
                                    return '<span class="label label-warning">状态有误</span>';
                                }

                            }
                        },
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'paytime', title: __('Paytime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});