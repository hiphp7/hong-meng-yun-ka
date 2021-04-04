define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'plugin/index' + location.search,
                    add_url: 'plugin/add',
                    edit_url: 'plugin/edit',
                    del_url: 'plugin/del/',
                    multi_url: 'plugin/multi',
                    import_url: 'plugin/import',
                    table: 'plugin',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                // pk: 'id',
                // sortName: 'id',
                escape: false,
                columns: [
                    [
                        // {checkbox: true},
                        // {field: 'plugin', title: __('标识')},
                        {field: 'name', title: __('名称'), operate: 'LIKE'},
                        {field: 'description', title: __('Description'), operate: 'LIKE'},
                        {field: 'author', title: __('作者'), operate: 'LIKE'},
                        {field: 'version', title: __('Version'), operate: 'LIKE'},
                        {
                            field: 'status',
                            title: __('状态'),
                            formatter:function(value,row,index){
                                if(value == 'enable'){
                                    return `<a href="javascript:;" class="btn btn-xs btn-success disable" data-plugin="${row.plugin}" data-toggle="tooltip" data-original-title="点击禁用">已启用</a>`;
                                }else if(value == 'disable'){
                                    return `<a href="javascript:;" class="btn btn-xs btn-default enable" data-plugin="${row.plugin}" data-toggle="tooltip" data-original-title="点击启用">已禁用</a>`;
                                }else{
                                    return `<a href="javascript:;" class="btn btn-xs btn-danger">状态有误</a>`;
                                }
                            }
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table, events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'dialog',
                                    title: __('配置'),
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-cog',
                                    url: 'plugin/setting/plugin_name/{plugin}',
                                    text:'配置',
                                    hidden:function(row){
                                        if(row.setting == false){
                                            return true;
                                        }
                                    }

                                },
                                {
                                    name: 'ajax',
                                    title: __('卸载'),
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    icon: 'fa fa-trash',
                                    confirm: '确认卸载这个插件？',
                                    text:'卸载',
                                    url: 'plugin/del/plugin_name/{plugin}',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Toastr.error(ret.msg);
                                        // Layer.alert(ret.msg);
                                        return false;
                                    }
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });



            // 为表格绑定事件
            Table.api.bindevent(table);



            //启用插件
            $(document).on("click", ".enable", function () {
                var plugin = $(this).data('plugin');
                layer.load();
                $.post("/admin/plugin/enable", {plugin:plugin, 'shelf': 0}, function(e){
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
//          禁用插件
            $(document).on("click", ".disable", function () {
                var plugin = $(this).data('plugin');
                layer.load();
                $.post("/admin/plugin/disable", {plugin:plugin, 'shelf': 1}, function(e){
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
        cjsc: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'plugin/cjsc' + location.search,
                    add_url: 'plugin/add',
                    edit_url: 'plugin/edit',
                    del_url: 'plugin/del/',
                    multi_url: 'plugin/multi',
                    import_url: 'plugin/import',
                    table: 'plugin',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                // pk: 'id',
                // sortName: 'id',
                search: false,
                showExport: false,
                commonSearch: false,
                escape: false,
                columns: [
                    [
                        // {checkbox: true},
                        // {field: 'plugin', title: __('标识')},
                        {field: 'name', title: __('名称'), operate: 'LIKE'},
                        {field: 'description', title: __('Description'), operate: 'LIKE'},
                        {field: 'author', title: __('作者'), operate: 'LIKE'},
                        {field: 'version', title: __('Version'), operate: 'LIKE'},
                        /*{
                            field: 'status',
                            title: __('状态'),
                            formatter:function(value,row,index){
                                if(value == 'enable'){
                                    return `<a href="javascript:;" class="btn btn-xs btn-success disable" data-plugin="${row.plugin}" data-toggle="tooltip" data-original-title="点击禁用">已启用</a>`;
                                }else if(value == 'disable'){
                                    return `<a href="javascript:;" class="btn btn-xs btn-default enable" data-plugin="${row.plugin}" data-toggle="tooltip" data-original-title="点击启用">已禁用</a>`;
                                }else{
                                    return `<a href="javascript:;" class="btn btn-xs btn-danger">状态有误</a>`;
                                }
                            }
                        },*/
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table, events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'dialog',
                                    title: __('配置'),
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-cog',
                                    url: 'plugin/setting/plugin_name/{plugin}',
                                    text:'配置',
                                    hidden:function(row){
                                        if(row.install == false || row.setting == false){
                                            return true;
                                        }
                                    }

                                },
                                {
                                    name: 'ajax',
                                    title: __('安装'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-wrench',
                                    confirm: '确认安装这个插件？',
                                    text:'安装',
                                    url: 'plugin/install',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Toastr.error(ret.msg);
                                        // Layer.alert(ret.msg);
                                        return false;
                                    },
                                    hidden:function(row){
                                        if(row.install == true){
                                            return true;
                                        }
                                    }
                                },
                                {
                                    name: 'ajax',
                                    title: __('卸载'),
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    icon: 'fa fa-trash',
                                    confirm: '确认卸载这个插件？',
                                    text:'卸载',
                                    url: 'plugin/del/plugin_name/{plugin}',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Toastr.error(ret.msg);
                                        // Layer.alert(ret.msg);
                                        return false;
                                    },
                                    hidden:function(row){
                                        if(row.install == false){
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



            // 为表格绑定事件
            Table.api.bindevent(table);



            //启用插件
            $(document).on("click", ".enable", function () {
                var plugin = $(this).data('plugin');
                layer.load();
                $.post("/admin/plugin/enable", {plugin:plugin, 'shelf': 0}, function(e){
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
//          禁用插件
            $(document).on("click", ".disable", function () {
                var plugin = $(this).data('plugin');
                layer.load();
                $.post("/admin/plugin/disable", {plugin:plugin, 'shelf': 1}, function(e){
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
        setting: function () {
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