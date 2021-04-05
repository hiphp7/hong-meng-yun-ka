define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'echarts', 'echarts-theme', 'template'], function ($, undefined, Backend, Datatable, Table, Echarts, undefined, Template) {

    var Controller = {
        index: function () {


            // 弹窗自适应宽高
            // var area = Fast.config.openArea != undefined ? Fast.config.openArea : [$(window).width() > 800 ? '800px' : '95%', $(window).height() > 600 ? '600px' : '95%'];

            /**
             * 下载更新
             * */
            $(document).on("click", "#download-update", function(){
                if($(this).html() == "正在更新..." || $(this).html() == "更新完成！"){
                    return;
                }
                var href = $(this).data("href");
                $(this).html("正在更新...");
                $.get("/admin/upgrade/index", function(e){
                    if(e.code == 200){
                        $("#download-update").html(e.msg);
                        // location.reload();
                    }else{
                        $("#download-update").html(e.msg);
                    }
                }, "json");
            });

            /**
             * 检查更新
             * */
            $(document).on("click", "#check-update", function(){
                if($(this).html() != "检查更新"){
                    return;
                }
                $(this).html("正在检查...");

                $.get("/admin/dashboard/checkUpgrade", function(e){
                    if(e.code == 400 && e.msg == '暂无更新'){
                        $("#check-update").html("当前已是最新版本");
                    }else if(e.code == 200){
                        $("#check-update").html("发现新版本v" + e.data.version + " <a data-href='/admin/upgrade/index/file/' id='download-update' style='cursor: pointer;'>下载更新</a>");
                    }else if(e.code == 401){
                        $("#check-update").html(e.msg);
                    }
                }, "json");

            })

        }
    };

    return Controller;
});
