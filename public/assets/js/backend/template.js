define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'template'], function ($, undefined, Backend, Table, Form, Template) {

    var Controller = {
        index: function () {




            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'template/index' + location.search,
                    add_url: 'template/upload',
                    // import_url: 'template/upload',
                    del_url: 'template/del',
                    multi_url: 'template/multi',
                    setting_url: 'template/setting2',
                    table: 'template',
                }
            });

            var table = $("#table");



            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                templateView: true,
                pk: 'id',
                sortName: 'id',
                commonSearch: false,
                visible: false,
                showToggle: false,
                showColumns: false,
                search:false,
                showExport: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name')},
                        {field: 'author', title: __('Author')},
                        {field: 'version', title: __('Version')},
                        {field: 'status', title: __('Status')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);


            table.on("post-body.bs.table", function(){
                $(".btn-setting").data("area", ["1000px", "800px"]);
            });

            require(['upload'], function (Upload) {
                Upload.api.plupload("#plupload-addon", function (data, ret) {
                    Toastr.success(ret.msg);
                    table.bootstrapTable('refresh', {});
                });
            });



            $(document).on("click", ".set-all", function () {
                var id = $(this).attr('data-id');
                var data = {id:id};
                $.post("/admin/template/set_all", data, function(e){
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
            $(document).on("click", ".set-pc", function () {
                var id = $(this).attr('data-id');
                var data = {id:id};
                $.post("/admin/template/set_pc", data, function(e){
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
            $(document).on("click", ".set-mobile", function () {
                var id = $(this).attr('data-id');
                var data = {id:id};
                $.post("/admin/template/set_mobile", data, function(e){
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

            $(document).on("click", ".set-default", function () {
                var id = $(this).attr('data-id');
                var data = {id:id};
                $.post("/admin/template/set_default", data, function(e){
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
        add: function () {
            Controller.api.bindevent();
        },
        setting: function () {
            Controller.api.bindevent();
        },
        upload: function () {
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
