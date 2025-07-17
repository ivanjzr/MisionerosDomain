$(document).ready(function(){

    /*
     * defaults para s2
     * */
    $.fn.select2.defaults.dropdownAutoWidth = true;

    /*
     *
     * Select 2 Extended
     *
     * */
    $.S2Ext = function (options) {
        var options = options || {};

        // if plugin exists
        if ( $("#"+options.S2ContainerId).data('select2') ){
            $("#"+options.S2ContainerId).empty();
            $("#"+options.S2ContainerId).select2("destroy");
            $("#"+options.S2ContainerId).trigger("change");
        }

        //
        if ($("#"+options.S2ContainerId).length){

            //
            if (options.enable){
                $("#"+options.S2ContainerId).removeAttr("disabled");
                $("#"+options.S2ContainerId).trigger("change");
            }

            // evt before load
            if (options.beforeLoad && $.isFunction(options.beforeLoad)){
                options.beforeLoad();
            }

            // add empty option
            if (options.addEmptyOption){
                $("#"+options.S2ContainerId).append("<option></option>");
            }

            // remote
            if ( options.remote && options.remote.url ){

                var obj_options = {
                    placeholder: options.placeholder,
                    allowClear: options.allowClear,
                    language: options.language,
                    minimumInputLength: options.minimumInputLength,
                    containerCssClass: (options.containerCssClass) ? options.containerCssClass : "",
                    dropdownCssClass: (options.dropdownCssClass) ? options.dropdownCssClass : "",
                    escapeMarkup: function (markup) { return markup; },
                    ajax: {
                        url: options.remote.url,
                        dataType: options.remote.dataType,
                        delay: options.remote.delay,
                        data: function (params) {
                            var ret_obj = {
                                qsval: options.remote.qsval,
                                q: params.term,
                                page: params.page
                            }; 
                            if ( options.remote.qs && $.isFunction(options.remote.qs) && options.remote.qs().name){ 
                                ret_obj[options.remote.qs().name] = options.remote.qs().val;
                            }
                            return ret_obj;
                        },
                        processResults: options.remote.processResults,
                        cache: options.remote.cache
                    },
                    templateResult: options.remote.templateResult,
                    templateSelection: options.remote.templateSelection
                };

                // NUEVO: Agregar dropdownParent si se proporciona
                if (options.dropdownParent) {
                    obj_options.dropdownParent = options.dropdownParent;
                }

                // NUEVO: Agregar width si se proporciona
                if (options.width) {
                    obj_options.width = options.width;
                }

                // NUEVO: Agregar closeOnSelect si se proporciona
                if (options.closeOnSelect !== undefined) {
                    obj_options.closeOnSelect = options.closeOnSelect;
                }

                // NUEVO: Agregar tags si se proporciona
                if (options.tags !== undefined) {
                    obj_options.tags = options.tags;
                }

                // NUEVO: Agregar multiple si se proporciona
                if (options.multiple !== undefined) {
                    obj_options.multiple = options.multiple;
                }

                // NUEVO: Agregar maximumSelectionLength si se proporciona
                if (options.maximumSelectionLength) {
                    obj_options.maximumSelectionLength = options.maximumSelectionLength;
                }

                if (options.newopts){
                    $.extend(obj_options, options.newopts);
                }

                $("#"+options.S2ContainerId).select2(obj_options);

                // set default selected id
                if ($.isNumeric(options.remote.selectedId) && options.remote.selectedText && options.remote.selectedHtml){
                    $("#"+options.S2ContainerId).append("<option value='"+options.remote.selectedId+"' selected='selected'>"+options.remote.selectedText+"</option>");
                    $("#"+options.S2ContainerId).trigger("change");
                    $("#select2-"+options.S2ContainerId+"-container").html(options.remote.selectedHtml);
                }

                //
                if (options.onClearing && $.isFunction(options.onClearing)){
                    $('#'+options.S2ContainerId).on("select2:unselecting", options.onClearing);
                }
            }

            // local
            else if (options.local && options.local.data){

                var local_options = {
                    placeholder: options.placeholder,
                    allowClear: options.allowClear,
                    language: options.language,
                    data: options.local.data,
                    escapeMarkup: function (markup) { return markup; },
                    templateResult: options.local.templateResult,
                    templateSelection: options.local.templateSelection
                };

                // NUEVO: Agregar dropdownParent para local también
                if (options.dropdownParent) {
                    local_options.dropdownParent = options.dropdownParent;
                }

                // NUEVO: Agregar width para local también
                if (options.width) {
                    local_options.width = options.width;
                }

                // NUEVO: Agregar closeOnSelect para local también
                if (options.closeOnSelect !== undefined) {
                    local_options.closeOnSelect = options.closeOnSelect;
                }

                // NUEVO: Agregar tags para local también
                if (options.tags !== undefined) {
                    local_options.tags = options.tags;
                }

                // NUEVO: Agregar multiple para local también
                if (options.multiple !== undefined) {
                    local_options.multiple = options.multiple;
                }

                // NUEVO: Agregar maximumSelectionLength para local también
                if (options.maximumSelectionLength) {
                    local_options.maximumSelectionLength = options.maximumSelectionLength;
                }

                $("#"+options.S2ContainerId).select2(local_options);

                // set default selected id
                if ($.isNumeric(options.local.selectedId)){
                    $('#'+options.S2ContainerId).val(options.local.selectedId).trigger("change");
                }
            }

            //
            if (options.unSelecting && $.isFunction(options.unSelecting)){
                $('#'+options.S2ContainerId).on("select2:unselecting", options.unSelecting);
            }

            /*
             * Disabled
             * */
            if (options.initDisabled){
                $('#'+options.S2ContainerId).prop("disabled", true);
            }

            // evt load completed
            if (options.loadComplete && $.isFunction(options.loadComplete)){
                options.loadComplete();
            }

            // evt on changed
            if (options.onChanged && $.isFunction(options.onChanged)){
                $("#"+options.S2ContainerId).unbind("select2:select").bind("select2:select", function (e) {
                    var el_id = $(this).val();
                    var sdata = $('#'+options.S2ContainerId).select2('data');
                    var res_data = (sdata && sdata[0]) ? sdata[0] : null;
                    options.onChanged(el_id, res_data); 
                });
            }

            $("#"+options.S2ContainerId).unbind("select2:open").bind("select2:open", function (e) {
                $(".select2-search__field")[0].focus();
            });

            $("#"+options.S2ContainerId).unbind("select2:close").bind("select2:close", function (e) {
                setTimeout(function(){
                    $("#"+options.S2ContainerId).select2("close");
                }, 100);
                if (options.onClose && $.isFunction(options.onClose)){
                    options.onClose();
                }
            });

            $("#"+options.S2ContainerId)
                .removeAttr("readonly")
                .removeAttr("disabled");

        } else {
            alert("not a valid " + options.S2ContainerId + " element for select 2");
        }
    }

}(jQuery));


//
function s2val(s2_id){
    var s2_data = $('#'+s2_id).select2('data');
    if ( s2_data[0] && s2_data[0].id ){
        return s2_data[0];
    } else {
        return false;
    }
}

//
function setSelect2Value(s2_id, selectedId, selectedText, selectedHtml){
    $("#"+s2_id).append("<option value='"+selectedId+"' selected='selected'>"+selectedText+"</option>");
    $("#"+s2_id).trigger("change");
    var container_id = "#select2-"+s2_id+"-container";
    if ($(container_id).length){
        $(container_id).html(selectedHtml);
    }
}

function s2ResetValue(s2_id){
    $(s2_id).val('').trigger('change');
    //$("#product_id").empty().trigger('change')
}


function s2Clear(elem_id){
    if ( $("#"+elem_id).data('select2') ){
        $("#"+elem_id).empty();
        $("#"+elem_id).select2("destroy");
        $("#"+elem_id).trigger("change");
    }
}
