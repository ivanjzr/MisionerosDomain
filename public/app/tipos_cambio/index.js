(function ($) {
    'use strict';




    //
    pageSetUp();



    /*
     * Refresh
     * */
    $('.btnReload').click(function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        //
        $('#grid_section').trigger("reloadGrid");
    });






























    //-------------------------- filter starts

    /*
     * Quitar Filtros
     * */
    $('.btnClearFilter').click(function(e){
        e.preventDefault();
        e.stopImmediatePropagation();
        //
        delayBtn('.btnClearFilter');
        //
        $('#filter_search').val("");
        //
        resetGrid('#grid_section');
    });





    //
    function doFilter(){
        //
        delayBtn('.btnFilter');
        //
        var param_filter = {};
        //
        var filter_search = $('#filter_search').val();
        //
        if ( filter_search ){
            param_filter.filter_search = filter_search;
        }
        //
        filterGrid( '#grid_section', param_filter );
    }

    //
    $("#filter_search").keypressDelay(function(){
        doFilter();
    });

    /*
     * Filtar
     * */
    $('.btnFilter').click(function(e){
        e.preventDefault();
        e.stopImmediatePropagation();
        //
        doFilter();
    });
    //-------------------------- filter ends
























     //
    $('#grid_section')
        .jqGrid({
            url: api_url + "/tipos-cambio/?h=1",
            datatype: "json",
            colNames:['id', 'tipo de cambio', 'monto', ''],
            colModel:[
                {name:'id', index:'id', width:10, hidden:true},
                {name:'nombre', index:'nombre', sortable:false, width:10, formatter: function(nombre, options, rawObject){
                        return nombre + " (" + rawObject.clave + ")";
                    }},
                {name:'monto', index:'monto', width:50},
                {name:'actions', index:'actions', width:10, formatter: function(cellValue, options, rawObject){
                    //
                    var str_btns = "<div style='text-align: center;'>";
                    //
                        str_btns += " <button type='button' class='oper-btn btn btn-success btn-editar' data-row-id='"+rawObject.id+"'><i class='fa fa-pencil-square-o'></i></button> ";
                    //
                    str_btns += "</div>";
                    //
                    return str_btns;
                }}
            ],
            gridview: true,
            jsonReader : {
                repeatitems: false,
                root: 'rows',
                page: 'currpage',
                total: 'totalpages',
                records: 'totalrecords'
            },
            loadComplete: function(){


                /*
                 * Editar Registro
                 * */
                $('.btn-editar').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //
                    var record_id = $(this).data("row-id");

                    disable_btns();
                    preload(true);

                    //
                    $.ajax({
                        type:'GET',
                        url: api_url + "/tipos-cambio/" + record_id,
                        success:function(response){

                            //
                            enable_btns();
                            preload(false);

                            //
                            if ( response && response.id ){

                                //
                                loadFormModal({
                                    modal_name: "modal-section",
                                    modal_size: "md",
                                    host_url: "",
                                    html_tmpl_url: "/app/tipos_cambio/modals/edit-record.html",
                                    js_handler_url: "/app/tipos_cambio/modals/edit-record.js",
                                    onBeforeLoad: function(){
                                        disable_btns();
                                        preload(true);
                                    },
                                    onReady: function(moduleReady){
                                        //
                                        enable_btns();
                                        preload(false);
                                        //
                                        moduleReady(response);
                                    }
                                });


                            }
                            //
                            else if (response.error){
                                smartalert("Error", "<i class='fa fa-clock-o'></i> <i>"+response.error+"</i>", "#C46A69", "fa fa-times fa-2x fadeInRight animated");
                            }
                            //
                            else {
                                smartalert("Error", "<i class='fa fa-clock-o'></i> <i>No se pudo completar la operación. Verifica tu conexión o contacta al administrador al intentar obtener datos del registro</i>", "#C46A69", "fa fa-times fa-2x fadeInRight animated");
                            }
                        },
                        error: function(){
                            //
                            enable_btns();
                            preload(false);
                            //
                            smartalert("Error", "<i class='fa fa-clock-o'></i> <i>No se pudo completar la operación. Verifica tu conexión o contacta al administrador al intentar obtener datos del registro</i>", "#C46A69", "fa fa-times fa-2x fadeInRight animated");
                        }
                    });

                });



            },
            pager: '#grid_section_pager',
            viewrecords: true,
            altRows: false,
            autowidth: true,
            height: 500,
            sortname:'id',
            sortorder: 'desc',
            rowNum: 25,
            rowList:[50, 100, 250, 500]
        });

    // toadd
    setupGrid();
    $(window).on('resize.jqGrid', function() {
        $("#grid_section").jqGrid('setGridWidth', $("#grid_container").width());
    })






    //------------- section config
    //
    $('.section_title').text("Tipos de Cambio");

    // show help
    $('#btnShowHelp').show();

    // show btns
    $('#header-buttons').show();
    //$('#footer-buttons').show();


    // set initial alert
    //var btn_str = "Note: You must delete the data-attribute* associated in order to act as true. For example <code> data-widget-togglebutton='true'</code> will still act as <code>false</code>, you must remove the <strong>data-attribute</strong> for the statement to be true.";
    //addAlert(".section_alerts_container", 'info', btn_str, true, function(){});




})(jQuery);