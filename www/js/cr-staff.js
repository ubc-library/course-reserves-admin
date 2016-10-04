var debug = true, debugmsg = 'log disabled';
function log(obj) {
    debug ? console.log(obj) : console.log(debugmsg);
}
$(document).on('reveal:open', '.reveal-modal', function () {
    $('html,body').css('overflow', 'hidden');
});
$(document).on('reveal:opened', '.reveal-modal', function () {
});
$(document).on('reveal:close', '.reveal-modal', function () {
    $('html,body').css('overflow', 'auto');
});
jQuery.curCSS = jQuery.css;
//start here
var itemsShown = {};

$(function () {
    $('.accordion').on('show', function (e) {
        $(e.target).prev('.accordion-heading').find('.accordion-toggle').removeClass('collapsed');
        $(e.target).prev('.accordion-heading').next('.accordion-body').css('height', 'auto').css('overflow', 'visible');
        $(e.target).prev('.accordion-heading').find('i.icon-chevron-right').removeClass('icon-chevron-right icon-chevron-down').addClass('icon-chevron-down');
        e.stopPropagation();
    });
    $('.accordion').on('hide', function (e) {
        $(this).find('.accordion-toggle').not($(e.target)).addClass('collapsed');
        $(e.target).prev('.accordion-heading').next('.accordion-body').css('height', '0px').css('overflow', 'hidden');
        $(e.target).prev('.accordion-heading').find('i.icon-chevron-down').removeClass('icon-chevron-down icon-chevron-right').addClass('icon-chevron-right');
        e.stopPropagation();
    });

    $('.accordion-item').on('show', function (e) {
        var cid = $(this).data('courseid');
        itemsShown[cid] = setInterval(function () {
            pendingChanges(cid)
        }, 3000);
    });
    $('.accordion-item').on('hide', function (e) {
        var cid = $(this).data('courseid');
        clearInterval(itemsShown[cid]);
        var position = $.inArray(cid, itemsShown);
        if (~position) itemsShown.splice(position, 1);
    });
});


function notify(msg, level, autoclose, reload, redirect) {
    level = typeof level !== 'undefined' ? level : 'default'; //default, info, danger, warning, alert, success
    redirect = typeof redirect !== 'undefined' ? redirect : false; //default, info, danger, warning, alert, success

    $('#status-update-message').empty();
    $('#status-updates-modal').removeClass('modal-info').removeClass('modal-alert').removeClass('modal-danger').removeClass('modal-warning').removeClass('modal-success').removeClass('modal-default').addClass('modal-default');
    $('#status-updates-modal').reveal({
        animation: 'fade',
        animationspeed: 1000,
        closeOnBackgroundClick: false,
        open: function () {
            $('#status-update-message').html(msg);
        },
        opened: function () {
            $(this).addClass('modal-' + level);
            if (autoclose) {
                setTimeout(function () {
                    $('#status-updates-modal').trigger('reveal:close');
                }, 1750);

            }
        },
        closed: function () {
            if (redirect) {
                window.location = redirect;
            }
            else if (reload) {
                window.location.reload();
            }
        }
    });
}

function addNote(item_id, course_id, role, identifier, puid) {
    var id = '#' + item_id + '-' + course_id + '-' + identifier + '-note';
    notes = $.get(
        '/passtolicr.php',
        {
            command: 'AddCINote',
            author_puid: puid,
            content: $(id).val(),
            item_id: item_id,
            course: course_id,
            roles_multi: role
        },
        function (data) {
            if (!data.success) {
                alert("Could not add note");
            }
            else {
                notify('Added note successfully. Reloading page..', 'success', true, true);
            }
        },
        'json');
}

function requiredFieldsFilled() {
    //"use strict";
    $('#bibliographic-details-success').empty();
    var proceed = true, field = "Missing Reqired Information: ";
    $("div#bibliographic-details-section").find("input").each(function () {
        if (this.getAttribute('data-required') === 'true' && $(this).val() === '') {
            field = field + $(this).prev('span').text() + ", ";
            proceed = false;
        }
    });
    // catch docstore files
    if ($("#upload_file_docstore") !== null && $("#upload_file_docstore").length) {
        $("#upload_file_docstore").contents().find("input").each(function () {
            if (this.getAttribute('data-required') === 'true' && $(this).val() === '') {
                field = field + $('#upload_file_docstore').prev('span').text() + ", ";
                proceed = false;
            }
        });
    }
    field = field.replace(/^(,|\s)+|(,|\s)+$/g, '');
    if (proceed) {
        $('#bibliographic-details-success').empty();
        $('#bib-data-save').removeClass('btn-dead-link').text('Save Changes').attr('disabled', false);
    } else {
        $('#bibliographic-details-success').text(field);
        $('#bib-data-save').addClass('btn-dead-link').text('Cannot Save Changes').attr('disabled', true);
    }
}

function pendingChanges(cid) {
    log('hello');
    $('#' + cid + '-pending-changes').empty().html(Math.random());
    var changed = false;
    var field = "Pending Changes: ";
    $("div#request-details-section-" + cid).find("input").each(function () {
        //log($(this).val());
        /*if (this.getAttribute('data-required') === 'true' && $(this).val() === '') {
         field = field + $(this).prev('span').text() + ", ";
         changed = true;
         }*/
    });

    field = field.replace(/^(,|\s)+|(,|\s)+$/g, '');
    if (changed) {
        $('#' + cid + '-pending-changes').empty().css('background-color', 'wheat').html(field);
    } else {
        $('#' + cid + '-pending-changes').empty();
    }
}

function highlightAvailableAccordion(status, selector) {
    log(status);
    var available = [15, 14, 24, 5, 6, 7, 28, 23, 27];
    if ($.inArray(status, available) > -1) {
        $(selector).find('.accordion-heading').find('a').addClass('accn-item-available');
    }
}

function changeItemFormat(itemid, course_id, formatid, physformat, loanperiod, puid) {
    log("Changing item " + itemid + " in course " + course_id + " to new formatid " + formatid + " and physformat " + physformat + " for loan period " + loanperiod + " started by user " + puid);

    var newItemID;

    var getInfo = $.get(
        '/passtolicr.php',
        {
            command: 'UpdateItemPhysicalFormat',
            item_id: itemid,
            course: course_id,
            type_id: formatid,
            physical_format: physformat
        },
        function (data) {
            if (!data.success) {
                alert('Could not start the conversion process. Please contact LSIT.')
            } else {
                if (data.data == 0) {
                    notify("Item not modified.");
                }
                else {
                    var newItemID = data.data;
                    console.log("New item ID " + newItemID);
                    if ($.inArray(physformat, ['pdf_general', 'pdf_article', 'pdf_chapter', 'pdf_other', 'ebook_general', 'ebook_chapter', 'electronic_article']) === -1) {
                        console.log("New item ID ");
                        var scilp = $.get(
                            '/passtolicr.php',
                            {
                                command: 'SetCILoanPeriod',
                                course: course_id,
                                item_id: newItemID != itemid ? newItemID : itemid,
                                loanperiod: 17
                            },
                            function (d) {
                                if (!d.success) {
                                    alert('Could not change the Loan Period. Please contact LSIT.')
                                } else {
                                    if (newItemID != itemid) {
                                        //we got a new item id because there were multiple requests on the old item
                                        notify('Complete. New item id: ' + newItemID + '. Redirecting to new item page...', 'success', true, true, '/details.item/id/' + newItemID);
                                    } else {
                                        notify('Successfully changed item format. Reloading page for new Bibliographic Details layout...', 'success', true, true);
                                    }
                                }
                            },
                            'json');
                    } else {
                        //some sort of success
                        if (newItemID != itemid) {
                            //we got a new item id because there were multiple requests on the old item
                            notify('Complete. New item id: ' + newItemID + '. Redirecting to new item page...', 'success', true, true, '/details.item/id/' + newItemID);
                        } else {
                            notify('Successfully changed item format. Reloading page for new Bibliographic Details layout...', 'success', true, true);
                        }
                    }
                }
            }
        },
        'json');
}


function addAltURL(itemid, url, desc, format) {
    notes = $.get(
        '/passtolicr.php',
        {
            command: 'AddItemAlternateURL',
            item_id: itemid,
            url: url,
            description: desc,
            format: format
        },
        function (data) {
            if (!data.success) {
                alert("Could not add note");
            }
            else {
                $('#add-url-modal').trigger('reveal:close');
                showSavedTimeout('url')

            }
        },
        'json');
    return false;
}

function editPrimUrl(id) {

    $('#edit-prim-url-modal').reveal({
        animation: 'fade',
        animationspeed: 300,
        closeonbackgroundclick: true,
        dismissmodalclass: 'close-reveal-modal',
        open: function () {
            $('#edit-prim-url-uri').val($('#' + id + '-uri').val());
            $('#edit-prim-url-id').val(id);
        },
        opened: function () {
        },
        close: function () {
        }
    });

}

function editAltUrl(id) {

    $('#edit-alt-url-modal').reveal({
        animation: 'fade',
        animationspeed: 300,
        closeonbackgroundclick: true,
        dismissmodalclass: 'close-reveal-modal',
        open: function () {
            $('#edit-alt-url-uri').val($('#' + id + '-uri').val());
            $('#edit-alt-url-id').val(id);
        },
        opened: function () {
        },
        close: function () {
        }
    });

}

function editPrimUrlSubmit(id) {

    notes = $.get(
        '/passtolicr.php',
        {
            command: 'SetItemURI',
            item_id: id,
            uri: $('#edit-prim-url-uri').val()
        },
        function (data) {
            if (!data.success) {
                alert("Could not update url");
            }
            else {
                $('#edit-prim-url-modal').trigger('reveal:close');
                showSavedTimeout('url')
            }
        },
        'json');
}

function editAltUrlSubmit(id) {
    notes = $.get(
        '/passtolicr.php',
        {
            command: 'UpdateItemAlternateURL',
            alternate_url_id: id,
            url: $('#edit-alt-url-uri').val()
        },
        function (data) {
            if (!data.success) {
                alert("Could not update url");
            }
            else {
                $('#edit-alt-url-modal').trigger('reveal:close');
                showSavedTimeout('url')

            }
        },
        'json');
}

function deleteAltUrl(id) {
    notes = $.get(
        '/passtolicr.php',
        {
            command: 'DeleteItemAlternateURL',
            alternate_url_id: id
        },
        function (data) {
            if (!data.success) {
                alert("Could not delete url");
            }
            else {
                $('#edit-alt-url-modal').trigger('reveal:close');
                showSavedTimeout('url')
            }
        },
        'json');
}

function addTag(itemid, courseid, value) {

    var msg = '';

    $.ajax({
        type: "POST",
        async: false,
        url: "/update.addtag",
        data: {
            i: itemid,
            c: courseid,
            t: value
        },
        dataType: 'json'
    })
        .done(function (data) {
            if (data.success) {
                msg = "Tag Added.";
            }
            else {
                msg = "Error saving tag: " + data.message;
            }
            console.log(data);
        })
        .fail(function (jqXHR, textStatus) {
            msg = " Fatal Error: Could not connect to server to save dates";
        });

    return msg;

}

function batchItemStatusUpdate(itemArray, status) {
    var formData = new FormData();
    formData.append("items", JSON.stringify(itemArray));
    formData.append("status", status);

    $.ajax({
        url: "/update.batchstatus",
        type: "POST",
        data: formData,
        dataType: "json",
        processData: false,
        cache: false,
        contentType: false
    }).done(function (data) {
        if (data.success) {
            alert(data.message);
            location.reload();
        }
    }).fail(function () {
        alert("Failed to update status of items");
    });

    return false;
}


function toggleCheckboxByClass(className, controlId) {

    var selectorToToggle = '.' + className + '-checkbox';

    var selectorControl = selectorToToggle + '-toggle';

    var controlMaster = $('#' + controlId);

    $(selectorControl).each(function () {
        $(this).prop("checked", controlMaster.prop("checked"));
    });

    $(selectorToToggle).each(function () {
        $(this).prop("checked", controlMaster.prop("checked"));
    });
}

/*!***************************************************
 * mark.js v6.1.0
 * https://github.com/julmot/mark.js
 * Copyright (c) 2014–2016, Julian Motz
 * Released under the MIT license https://git.io/vwTVl
 *****************************************************/
"use strict";function _classCallCheck(a,b){if(!(a instanceof b))throw new TypeError("Cannot call a class as a function")}var _extends=Object.assign||function(a){for(var b=1;b<arguments.length;b++){var c=arguments[b];for(var d in c)Object.prototype.hasOwnProperty.call(c,d)&&(a[d]=c[d])}return a},_createClass=function(){function a(a,b){for(var c=0;c<b.length;c++){var d=b[c];d.enumerable=d.enumerable||!1,d.configurable=!0,"value"in d&&(d.writable=!0),Object.defineProperty(a,d.key,d)}}return function(b,c,d){return c&&a(b.prototype,c),d&&a(b,d),b}}(),_typeof="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(a){return typeof a}:function(a){return a&&"function"==typeof Symbol&&a.constructor===Symbol?"symbol":typeof a};!function(a,b,c){"function"==typeof define&&define.amd?define(["jquery"],function(d){return a(b,c,d)}):"object"===("undefined"==typeof exports?"undefined":_typeof(exports))?a(b,c,require("jquery")):a(b,c,jQuery)}(function(a,b,c){var d=function(){function c(a){_classCallCheck(this,c),this.ctx=a}return _createClass(c,[{key:"log",value:function a(b){var c=arguments.length<=1||void 0===arguments[1]?"debug":arguments[1],a=this.opt.log;this.opt.debug&&"object"===("undefined"==typeof a?"undefined":_typeof(a))&&"function"==typeof a[c]&&a[c]("mark.js: "+b)}},{key:"escapeStr",value:function(a){return a.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g,"\\$&")}},{key:"createRegExp",value:function(a){return a=this.escapeStr(a),Object.keys(this.opt.synonyms).length&&(a=this.createSynonymsRegExp(a)),this.opt.diacritics&&(a=this.createDiacriticsRegExp(a)),a=this.createAccuracyRegExp(a)}},{key:"createSynonymsRegExp",value:function(a){var b=this.opt.synonyms;for(var c in b)if(b.hasOwnProperty(c)){var d=b[c],e=this.escapeStr(c),f=this.escapeStr(d);a=a.replace(new RegExp("("+e+"|"+f+")","gmi"),"("+e+"|"+f+")")}return a}},{key:"createDiacriticsRegExp",value:function(a){var b=["aÀÁÂÃÄÅàáâãäåĀāąĄ","cÇçćĆčČ","dđĐďĎ","eÈÉÊËèéêëěĚĒēęĘ","iÌÍÎÏìíîïĪī","lłŁ","nÑñňŇńŃ","oÒÓÔÕÕÖØòóôõöøŌō","rřŘ","sŠšśŚ","tťŤ","uÙÚÛÜùúûüůŮŪū","yŸÿýÝ","zŽžżŻźŹ"],c=[];return a.split("").forEach(function(d){b.every(function(b){if(-1!==b.indexOf(d)){if(c.indexOf(b)>-1)return!1;a=a.replace(new RegExp("["+b+"]","gmi"),"["+b+"]"),c.push(b)}return!0})}),a}},{key:"createAccuracyRegExp",value:function(a){switch(this.opt.accuracy){case"partially":return"()("+a+")";case"complementary":return"()(\\S*"+a+"\\S*)";case"exactly":return"(^|\\s)("+a+")(?=\\s|$)"}}},{key:"getSeparatedKeywords",value:function(a){var b=this,c=[];return a.forEach(function(a){b.opt.separateWordSearch?a.split(" ").forEach(function(a){a.trim()&&c.push(a)}):a.trim()&&c.push(a)}),{keywords:c,length:c.length}}},{key:"getElements",value:function(){var a=void 0,b=[];return a="undefined"==typeof this.ctx?[]:this.ctx instanceof HTMLElement?[this.ctx]:Array.isArray(this.ctx)?this.ctx:Array.prototype.slice.call(this.ctx),a.forEach(function(a){b.push(a);var c=a.querySelectorAll("*");c.length&&(b=b.concat(Array.prototype.slice.call(c)))}),a.length||this.log("Empty context","warn"),{elements:b,length:b.length}}},{key:"matches",value:function(a,b){return(a.matches||a.matchesSelector||a.msMatchesSelector||a.mozMatchesSelector||a.webkitMatchesSelector||a.oMatchesSelector).call(a,b)}},{key:"matchesFilter",value:function(a,b){var c=this,d=!0,e=this.opt.filter.concat(["script","style","title"]);return this.opt.iframes||(e=e.concat(["iframe"])),b&&(e=e.concat(["*[data-markjs='true']"])),e.every(function(b){return c.matches(a,b)?d=!1:!0}),!d}},{key:"onIframeReady",value:function(a,b,c){try{!function(){var d=a.contentWindow,e="about:blank",f="complete",g=function(){try{if(null===d.document)throw new Error("iframe inaccessible");b(d.document)}catch(a){c()}},h=function(){var b=a.getAttribute("src").trim(),c=d.location.href;return c===e&&b!==e&&b},i=function(){var b=function b(){try{h()||(a.removeEventListener("load",b),g())}catch(a){c()}};a.addEventListener("load",b)};d.document.readyState===f?h()?i():g():i()}()}catch(a){c()}}},{key:"forEachElementInIframe",value:function(a,b){var c=this,d=arguments.length<=2||void 0===arguments[2]?function(){}:arguments[2],e=0,f=function(){--e<1&&d()};this.onIframeReady(a,function(a){var d=Array.prototype.slice.call(a.querySelectorAll("*"));0===(e=d.length)&&f(),d.forEach(function(a){"iframe"===a.tagName.toLowerCase()?!function(){var d=0;c.forEachElementInIframe(a,function(a,c){b(a,c),c-1===d&&f(),d++},f)}():(b(a,d.length),f())})},function(){var b=a.getAttribute("src");c.log("iframe '"+b+"' could not be accessed","warn"),f()})}},{key:"forEachElement",value:function(a){var b=this,c=arguments.length<=1||void 0===arguments[1]?function(){}:arguments[1],d=arguments.length<=2||void 0===arguments[2]?!0:arguments[2],e=this.getElements(),f=e.elements,g=e.length,h=function(){0===--g&&c()};h(++g),f.forEach(function(c){if(!b.matchesFilter(c,d)){if("iframe"===c.tagName.toLowerCase())return void b.forEachElementInIframe(c,function(c){b.matchesFilter(c,d)||a(c)},h);a(c)}h()})}},{key:"forEachNode",value:function(a){var b=arguments.length<=1||void 0===arguments[1]?function(){}:arguments[1];this.forEachElement(function(b){for(b=b.firstChild;b;b=b.nextSibling)3===b.nodeType&&b.textContent.trim()&&a(b)},b)}},{key:"wrapMatches",value:function(a,c,d,e){for(var f=this.opt.element?this.opt.element:"mark",g=d?0:2,h=void 0;null!==(h=c.exec(a.textContent));){var i=h.index;d||(i+=h[g-1].length);var j=a.splitText(i);if(a=j.splitText(h[g].length),null!==j.parentNode){var k=b.createElement(f);k.setAttribute("data-markjs","true"),this.opt.className&&k.setAttribute("class",this.opt.className),k.textContent=h[g],j.parentNode.replaceChild(k,j),e(k)}c.lastIndex=0}}},{key:"unwrapMatches",value:function(a){for(var c=a.parentNode,d=b.createDocumentFragment();a.firstChild;)d.appendChild(a.removeChild(a.firstChild));c.replaceChild(d,a),c.normalize()}},{key:"markRegExp",value:function(a,b){var c=this;this.opt=b,this.log('Searching with expression "'+a+'"');var d=!1,e=function(a){d=!0,c.opt.each(a)};this.forEachNode(function(b){c.wrapMatches(b,a,!0,e)},function(){d||c.opt.noMatch(a),c.opt.complete(),c.opt.done()})}},{key:"mark",value:function(a,b){var c=this;this.opt=b,a="string"==typeof a?[a]:a;var d=this.getSeparatedKeywords(a),e=d.keywords,f=d.length;0===f&&(this.opt.complete(),this.opt.done()),e.forEach(function(a){var b=new RegExp(c.createRegExp(a),"gmi"),d=!1,g=function(a){d=!0,c.opt.each(a)};c.log('Searching with expression "'+b+'"'),c.forEachNode(function(a){c.wrapMatches(a,b,!1,g)},function(){d||c.opt.noMatch(a),e[f-1]===a&&(c.opt.complete(),c.opt.done())})})}},{key:"unmark",value:function(a){var b=this;this.opt=a;var c=this.opt.element?this.opt.element:"*";c+="[data-markjs]",this.opt.className&&(c+="."+this.opt.className),this.log('Removal selector "'+c+'"'),this.forEachElement(function(a){b.matches(a,c)&&b.unwrapMatches(a)},function(){b.opt.complete(),b.opt.done()},!1)}},{key:"opt",set:function(b){this._opt=_extends({},{element:"",className:"",filter:[],iframes:!1,separateWordSearch:!0,diacritics:!0,synonyms:{},accuracy:"partially",each:function(){},noMatch:function(){},done:function(){},complete:function(){},debug:!1,log:a.console},b)},get:function(){return this._opt}}]),c}();c.fn.mark=function(a,b){return new d(this).mark(a,b),this},c.fn.markRegExp=function(a,b){return new d(this).markRegExp(a,b),this},c.fn.unmark=function(a){return new d(this).unmark(a),this}},window,document);