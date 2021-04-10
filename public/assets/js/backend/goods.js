define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'goods/index' + location.search,
                    add_url: 'goods/add',
                    stock_add: 'goods/stock_add',
                    // stock: 'goods/stock',
                    edit_url: 'goods/edit',
                    del_url: 'goods/del',
                    multi_url: 'goods/multi',
                    import_url: 'goods/import',
                    table: 'goods',
                }
            });


            var table = $("#table");



            $(".btn-add").data("area",["1000px","670px"]);
            $(".btn-edit").data("area",["1000px","670px"]);

            table.on('post-body.bs.table',function(){
                $(".btn-editone").data("area",["1000px","670px"]);
            })
            $.fn.bootstrapTable.locales[Table.defaults.locale]['formatSearch'] = function(){return "请输入商品名称查询";};
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                commonSearch: false,
                columns: [
                    [
                        {checkbox: true},
                        // {field: 'site_id', title: __('Site_id')},
                        {field: 'category.name', title: __('分类')},
                        {field: 'name', title: __('Name')},
                        {
                            field: 'goods_type',
                            title: __('商品类型'),
                            formatter: function(value){
                                if(value == 0){
                                    return '<span class="label"  title="" style="background: #9c27b0;"> 卡 密 </span>';
                                }else if(value == 1){
                                    return '<span class="label"  title="" style="background: #00bcd4;">激 活 码</span>';
                                }else if(value == 2){
                                    return '<span class="label"  title="" style="background: #ff5722;">账号密码</span>';
                                }else if(value == 3){
                                    return '<span class="label"  title="" style="background: #00acc1;"> 图 片 </span>';
                                }else if(value == 4){
                                    return '<span class="label"  title="" style="background: #868686;"> 其 他 </span>';
                                }else if(value == 'duijie'){
                                    return '<span class="label"  title="" style="background: #4caf50;"> 对 接 </span>';
                                }
                            }
                        },
                        {
                            field: 'deliver',
                            title: __('发货方式'),
                            formatter: function(value){
                                if(value == 0){
                                    return '<span class="label"  title="" style="background: #18bc9c;">自动</span>';
                                }else if(value == 1){
                                    return '<span class="label"  title="" style="background: #e91e63;">手动</span>';
                                }
                            }
                        },
                        {field: 'price', title: __('价格'), operate:'BETWEEN'},
                        // {field: 'original_price', title: __('Original_price'), operate:'BETWEEN'},
                        {field: 'sales', title: __('销量')},
                        {
                            field: 'stock',
                            title: __('库存'),
                            formatter:function(value,row,index){
                                if(row.type == 'own'){
                                    return value;
                                }else if(row.type == 'jiuwu'){
                                    return '正常';
                                }else{
                                    return `<a href="javascript:;" class="btn btn-xs btn-danger">错误</a>`;
                                }
                            }
                        },
                        {field: 'images', title: __('图片'), events: Table.api.events.image, formatter: Table.api.formatter.images},
                        {
                            field: 'type',
                            title: __('商品来源'),
                            formatter: function(value){
                                if(value == 'own'){
                                    return '<span class="label"  title="" style="background: #18bc9c;">自营</span>';
                                }else if(value == 'jiuwu'){
                                    return '<span class="label"  title="" style="background: #3498db;">玖伍社区</span>';
                                }else{
                                    return '<span class="label"  title="">未知</span>';
                                }
                            }
                        },
                        {
                            field: 'shelf',
                            title: __('状态'),
                            formatter:function(value,row,index){
                                if(value == 0){
                                    return `<a href="javascript:;" class="btn btn-xs btn-success down-goods" data-id="${row.id}" data-toggle="tooltip" data-original-title="点击下架">上架中</a>`;
                                }else if(value == 1){
                                    return `<a href="javascript:;" class="btn btn-xs btn-default up-goods" data-id="${row.id}" data-toggle="tooltip" data-original-title="点击上架">已下架</a>`;
                                }else{
                                    return `<a href="javascript:;" class="btn btn-xs btn-danger">状态有误</a>`;
                                }
                            }
                        },
                        // {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'add_stock',
                                    title: __('添加库存'),
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-plus',
                                    url: 'goods/stock_add',
                                    text:'添加库存',
                                    hidden:function(row){
                                        if(row.deliver == 1 || row.type != 'own'){
                                            return true;
                                        }
                                    }

                                },
                                {
                                    name: 'admin_stock',
                                    title: __('管理库存'),
                                    classname: 'btn btn-xs btn-juse btn-dialog',
                                    icon: 'fa fa-cogs',
                                    url: 'goods/stock',
                                    text:'管理库存',
                                    hidden:function(row){
                                        if(row.deliver == 1 || row.type != 'own'){
                                            return true;
                                        }
                                    }
                                }
                            ],
                            formatter: Table.api.formatter.operate,
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


            //上架商品
            $(document).on("click", ".up-goods", function () {
                var id = $(this).attr('data-id');
                layer.load();
                $.post("/admin/goods/upGoods", {id:id, 'shelf': 0}, function(e){
                    if(e.code == 200){
                        layer.closeAll();
                        Toastr.success(e.msg);
                    }else{
                        layer.closeAll('loading');
                        Toastr.error(e.msg);
                    }
                    table.bootstrapTable('refresh', {});
                }).error(function(){
                    layer.closeAll('loading');
                    Toastr.error('服务器错误！');
                })
            });
//            下架商品
            $(document).on("click", ".down-goods", function () {
                var id = $(this).attr('data-id');
                layer.load();
                $.post("/admin/goods/downGoods", {id:id, 'shelf': 1}, function(e){
                    if(e.code == 200){
                        layer.closeAll();
                        Toastr.success(e.msg);
                    }else{
                        layer.closeAll('loading');
                        Toastr.error(e.msg);
                    }
                    table.bootstrapTable('refresh', {});
                }).error(function(){
                    layer.closeAll('loading');
                    Toastr.error('服务器错误！');
                })
            });


        },
        recyclebin: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'goods/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name'), align: 'left'},
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            width: '130px',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'Restore',
                                    text: __('Restore'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-restoreit',
                                    icon: 'fa fa-rotate-left',
                                    url: 'goods/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'goods/destroy',
                                    refresh: true
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        stock: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
//                    stock_del: 'goods/stock_del',
                    del_url: 'goods/stock_del',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'goods/stock' + location.search + "&ids=" + $('#ids').val(),
                pk: 'id',
                sortName: 'id',
                visible: false,
                showToggle: false,
                showColumns: false,
                showExport: false,
                search:false,
                commonSearch: false,
                columns: [
                    [
                        {checkbox: true},
                        {
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
                        },
                        {
                            field: 'createtime',
                            title: __('添加时间'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            $("input[name='row[deliver]']").change(function(){
                var deliver = $(this).val();
                if(deliver == 0){
                    $("#attach-box").hide();
                    $("#stock").hide();
                }else{
                    $("#attach-box").show();
                    $("#stock").show();
                }
            })
            Controller.api.bindevent();
        },
        stock_add: function () {

            Controller.api.bindevent();
        },
        edit: function () {

            $("input[name='row[deliver]']").change(function(){
                var deliver = $(this).val();
                if(deliver == 0){
                    $("#attach-box").hide();
                    $("#stock").hide();
                }else{
                    $("#attach-box").show();
                    $("#stock").show();
                }
            })


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
