define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'docking/docking_site/index' + location.search,
                    add_url: 'docking/docking_site/add',
                    edit_url: 'docking/docking_site/edit',
                    del_url: 'docking/docking_site/del',
                    multi_url: 'docking/docking_site/multi',
                    import_url: 'docking/docking_site/import',
                    table: 'docking_site',
                }
            });

            var table = $("#table");

            table.on('post-body.bs.table',function(){
                $(".btn-goods-list").data("area",["1000px","720px"]);
            })

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {
                            field: 'id',
                            title: __('ID'),
                            formatter: function(value, row, index){
                                return ++index;
                            }
                        },
                        {field: 'domain', title: __('站点域名'), operate: 'LIKE'},
                        {
                            field: 'type',
                            title: __('站点类型'),
                            operate: 'LIKE',
                            formatter: function(value){
                                if(value == 'jiuwu'){
                                    return '<span class="label"  title="" style="background: #00bcd4;">玖伍社区</span>';
                                }
                            }
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                /*{
                                    name: 'add_stock',
                                    title: __('添加库存'),
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-plus',
                                    url: 'goods/stock_add',
                                    text:'添加库存',
                                    hidden:function(row){
                                        if(row.deliver == 1){
                                            return true;
                                        }
                                    }

                                },*/
                                {
                                    name: 'admin_stock',
                                    title: __('商品列表'),
                                    classname: 'btn btn-xs btn-juse btn-dialog btn-goods-list',
                                    icon: 'fa fa-reorder fa-fw',
                                    url: 'docking/docking_site/goods_list',
                                    text:'商品列表',
                                    hidden:function(row){
                                        if(row.deliver == 1){
                                            return true;
                                        }
                                    }
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            Table.button.edit = {
                name: 'edit',
                text: __('编辑'),
                icon: 'fa fa-pencil',
                title: __('编辑'),
                classname: 'btn btn-xs btn-success btn-editone'
            }

            Table.button.del = {
                name: 'del',
                text: __('删除'),
                icon: 'fa fa-trash',
                title: __('删除'),
                classname: 'btn btn-xs btn-danger btn-delone'
            }

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        goods_list: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    sync_url: 'docking/docking_site/sync2'
                }
            });



            var table = $("#table");
            var site_id = $('#ids').val();
            // 初始化表格
            table.bootstrapTable({
                url: 'docking/docking_site/goods_list' + location.search + "&ids=" + site_id,
                pk: 'id',
                sortName: 'id',
                visible: false,
                showToggle: false,
                showColumns: false,
                showExport: false,
                search:false,
                commonSearch: false,
                pagination: false,
                columns: [
                    [
                        // {checkbox: true},
                        /*{
                            field: 'cdk',
                            title: __('卡密'),
                            align: 'center',
                            formatter:function(value,row,index){
                                if(row.type == 3){
                                    return `<a href="javascript:"><img class="img-sm img-center" src="${value}"></a>`;
                                }else{
                                    return value;
                                }
                            }
                        },*/
                        {field: 'id', title: __('商品ID')},
                        {field: 'title', title: __('商品名称')},
                        // {field: 'goods_type', title: __('业务模型ID')},
                        {field: 'look_price', title: __('业务价格')},
                        {field: 'minbuynum_0', title: __('最低下单数量')},
                        {field: 'maxbuynum_0', title: __('最高下单数量')},
                        /*{
                            field: 'createtime',
                            title: __('添加时间'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },*/
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'add_stock',
                                    title: __('同步至本站'),
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-plus',
                                    url: 'docking/docking_site/sync/site_id/' + site_id,
                                    text:'同步至本站',
                                    /*hidden:function(row){
                                        if(row.deliver == 1){
                                            return true;
                                        }
                                    }*/

                                },
                                /*{
                                    name: 'admin_stock',
                                    title: __('管理库存'),
                                    classname: 'btn btn-xs btn-juse btn-dialog',
                                    icon: 'fa fa-cogs',
                                    url: 'goods/stock',
                                    text:'管理库存',
                                    hidden:function(row){
                                        if(row.deliver == 1){
                                            return true;
                                        }
                                    }
                                }*/
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });



            // 为表格绑定事件
            Table.api.bindevent(table);

            // $(document).on("click", ".btn-sync", function(){
            //     layer.confirm()
            // })




        },
        sync: function () {
            Controller.api.bindevent();
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