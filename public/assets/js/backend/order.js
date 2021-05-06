define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            $.fn.bootstrapTable.locales[Table.defaults.locale]['formatSearch'] = function(){return "请输入订单号查询";};
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/index' + location.search,
                    edit_url: 'order/edit',
                    del_url: 'order/del',
                    table: 'order',
                }
            });

            var table = $("#table");

            // 初始化表格
            var tableOptions = {
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                commonSearch: false,
                columns: [
                    [
                        {checkbox: true},
                        // {field: 'id', title: __('Id')},
                        {field: 'order_no', title: __('订单号')},
                        {
                            field: 'user.nickname',
                            title: __('用户'),
                            formatter:function(value,row,index){
                                if(row.email){
                                    return row.email;
                                }else{
                                    return row.user.nickname;
                                }
                            }
                        },
                        {field: 'goods_name', title: __('Goods_name'), operate: 'LIKE'},
                        // {field: 'goods_cover', title: __('Goods_cover'), operate: 'LIKE'},
                        {field: 'goods_money', title: __('商品单价')},
                        {field: 'goods_num', title: __('Goods_num')},
                        {field: 'money', title: __('订单金额'), operate:'BETWEEN'},
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
            };

            // 初始化表格
            table.bootstrapTable(tableOptions);

            // 为表格绑定事件
            Table.api.bindevent(table);

            //绑定TAB事件
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                $('.search > input').val('')
                // var options = table.bootstrapTable(tableOptions);
                var typeStr = $(this).attr("href").replace('#', '');
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                options.queryParams = function (params) {
                    // params.filter = JSON.stringify({type: typeStr});
                    params.status = typeStr;


                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;

            });
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
