//-------------------------------------------------------------------
//------------------------ Helpers for ie8 --------------------------
//-------------------------------------------------------------------

// From https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/keys
if (!Object.keys) {
    Object.keys = (function () {
        'use strict';
        var hasOwnProperty = Object.prototype.hasOwnProperty,
            hasDontEnumBug = !({toString: null}).propertyIsEnumerable('toString'),
            dontEnums = [
                'toString',
                'toLocaleString',
                'valueOf',
                'hasOwnProperty',
                'isPrototypeOf',
                'propertyIsEnumerable',
                'constructor'
            ],
            dontEnumsLength = dontEnums.length;

        return function (obj) {
            if (typeof obj !== 'object' && (typeof obj !== 'function' || obj === null)) {
                throw new TypeError('Object.keys called on non-object');
            }

            var result = [], prop, i;

            for (prop in obj) {
                if (hasOwnProperty.call(obj, prop)) {
                    result.push(prop);
                }
            }

            if (hasDontEnumBug) {
                for (i = 0; i < dontEnumsLength; i++) {
                    if (hasOwnProperty.call(obj, dontEnums[i])) {
                        result.push(dontEnums[i]);
                    }
                }
            }
            return result;
        };
    }());
}

//console.log('PUID:'+ puid);
//-------------------------------------------------------------------
//-------- GO DOWN UNTIL ANOTHER OF THESE 3 LINED HEADINGS ----------
//-------------------------------------------------------------------

/* GO TO FORM CONSTRUCTORS WAY BELOW - DO NOT EDIT THIS STUFF HERE */

// A constructor for defining new Pre-submit Forms
function PreForm(options) {
    "use strict"; //JSLint simply won't shut up without this

    this.fields = {
        title: 0,
        journaltitle: 0,
        articletitle: 0,
        doi: 0,
        isbn: 0,
        callnumber: 0,
        pubmedid: 0,
        purl: 0,
        url: 0
    };

    this.machine_names = {
        title: "title",
        journaltitle: "jtitle",
        articletitle: "atitle",
        doi: "doi",
        isbn: "isbn",
        callnumber: "callno",
        pubmedid: "pmid",
        purl: "webcat",
        url: "url"
    };

    this.placeholders = {
        isbn: "ISXN",
        purl: "Permanent URL From UBC Library Catalogue:"
    };

    this.html = '';

    if (options.groupdata && options.groupdata.length) {
        this.html = this.html.concat('<p>Use one of the search groups below to find an Item.</p>');
        var i = 0;
        for (i; i < options.groupdata.length; i += 1) {
            if (Object.keys(options.groupdata[i].fields).length > 1){
                this.html = this.html.concat('<p><b>'+options.groupdata[i].title+'</b></p>');
                this.html = this.html.concat('<div class="row-fluid"><div class="span12">');
                for (var key in options.groupdata[i].fields) {
                    if (this.fields.hasOwnProperty(key)) {
                        this.fields[key] = 1;
                        this.html = this.html.concat('<div class="input-prepend"><span class="add-on span2">'+options.groupdata[i].fields[key]+'</span><input class="span10 stored" type="text" id="ubc_id_'+this.machine_names[key]+'" name="'+this.machine_names[key]+'" value="" placeholder="'+(this.placeholders[key] ? this.placeholders[key] : options.groupdata[i].fields[key])+'"></div>');
                    }
                }
                this.html = this.html.concat('</div></div>');
                this.html = this.html.concat('<b>OR</b>');
            } else {
                for (var key in options.groupdata[i].fields) {
                    if (this.fields.hasOwnProperty(key)) {
                        this.fields[key] = 1;
                        this.html = this.html.concat('<div class="row-fluid"><div class="span12"><div class="input-prepend"><span class="add-on span2">'+options.groupdata[i].fields[key]+'</span><input class="span10 stored" type="text" id="ubc_id_'+this.machine_names[key]+'" name="'+this.machine_names[key]+'" value="" placeholder="'+(this.placeholders[key] ? this.placeholders[key] : options.groupdata[i].fields[key])+'"></div></div></div>');
                    }
                    this.html = this.html.concat('<b>OR</b>');
                }
            }
        }
    } else {
        for (var key in options.fields) {
            if (this.fields.hasOwnProperty(key)) {
                this.fields[key] = 1;
                this.html = this.html.concat('<div class="row-fluid"><div class="span12"><div class="input-prepend"><span class="add-on span2">'+options.fields[key]+'</span><input class="span10 stored" type="text" id="ubc_id_'+this.machine_names[key]+'" name="'+this.machine_names[key]+'" value="" placeholder="'+(this.placeholders[key] ? this.placeholders[key] : options.fields[key])+'"></div></div></div>');
            }
            this.html = this.html.concat('<b>OR</b>');
        }
    }

    this.html = this.html.replace(/<b>OR<\/b>$/im,'<br/>');
    this.html = this.html.concat('<input type="hidden" id="ubc_id_type" name="type" value="'+options.type+'">');
    this.html = this.html.concat('<input type="hidden" id="ubc_id_locr_type" name="locr_type" value="'+options.locrType+'">');

    this.type = options.type;
    this.locrType = options.locrType;
    this.submitFields = options.submitFields;

}

// A constructor for defining new Submit Forms
function SubmitForm(options) {
    "use strict";

    this.fields = {

        title: {
            display: 0,
            display_name: "Title",
            machine_name: "title",
            placeholder: "Title",
            tooltip: "Please enter the title.",
            required: 0

        },
        articletitle: {
            display: 0,
            display_name: "Work Title",
            machine_name: "articletitle",
            placeholder: "e.g. Radiographic Rejects in Radiology",
            tooltip: "e.g. Radiographic Rejects in Radiology",
            required: 0

        },
        chaptertitle: {
            display: 0,
            display_name: "Chapter Title",
            machine_name: "chaptertitle",
            placeholder: "e.g. Radiographic Rejects in Radiology",
            tooltip: "e.g. Radiographic Rejects in Radiology",
            required: 0

        },
        articleauthors: {
            display: 0,
            display_name: "Work Author(s)",
            machine_name: "author",
            placeholder: "e.g. Jabbari, Nasrollah",
            tooltip: "e.g. Jabbari, Nasrollah",
            required: 0
        },
        creator: {
            display: 0,
            display_name: "Creator",
            machine_name: "author",
            placeholder: "e.g. Jabbari, Nasrollah",
            tooltip: "e.g. Jabbari, Nasrollah",
            required: 0
        },
        journaltitle: {
            display: 0,
            display_name: "Journal Title",
            machine_name: "journaltitle",
            placeholder: "Journal Title e.g Health",
            tooltip: "Journal Title e.g Health",
            required: 0

        },
        journalvolume: {
            display: 0,
            display_name: "Journal Volume",
            machine_name: "journalvolume",
            placeholder: "Volume e.g 44",
            tooltip: "Volume e.g 44",
            required: 0

        },
        journalissue: {
            display: 0,
            display_name: "Journal Issue",
            machine_name: "journalissue",
            placeholder: "Issue e.g 4",
            tooltip: "Issue e.g 4",
            required: 0

        },
        journalmonth: {
            display: 0,
            display_name: "Journal Month",
            machine_name: "journalmonth",
            placeholder: "Month e.g 02",
            tooltip: "Month e.g 02",
            required: 0

        },
        journalyear: {
            display: 0,
            display_name: "Journal Year",
            machine_name: "journalyear",
            placeholder: "Year e.g 2012",
            tooltip: "Year e.g 2012",
            required: 0

        },
        incpages: {
            display: 0,
            display_name: "Page(s)",
            machine_name: "incpages",
            placeholder: "e.g. 12-15",
            tooltip: "e.g. 12-15",
            required: 0

        },
        issn: {
            display: 0,
            display_name: "ISSN",
            machine_name: "issn",
            placeholder: "ISSN e.g 1949-4998",
            tooltip: "ISSN e.g 1949-4998",
            required: 0

        },
        doi: {
            display: 0,
            display_name: "DOI",
            machine_name: "doi",
            placeholder: "e.g 10.2165.123",
            tooltip: "e.g 10.2165.123",
            required: 0

        },
        classid: {
            display: 0,
            display_name: "ClassID",
            machine_name: "classid",
            placeholder: "Class ID",
            tooltip: "Class ID",
            required: 0

        },
        edition: {
            display: 0,
            display_name: "Edition",
            machine_name: "edition",
            placeholder: "e.g. 1",
            tooltip: "e.g. 1",
            required: 0

        },
        editor: {
            display: 0,
            display_name: "Editor",
            machine_name: "editor",
            placeholder: "e.g. Captain Sparrow",
            tooltip: "e.g. Captain Sparrow",
            required: 0

        },
        submittype: {
            display: 0,
            display_name: "submittype",
            machine_name: "submittype", //is this right?
            placeholder: "---",
            tooltip: "---",
            required: 0

        },
        publisher: {
            display: 0,
            display_name: "Publisher",
            machine_name: "publisher",
            placeholder: "Prentice Hall",
            tooltip: "Prentice Hall",
            required: 0

        },
        pubplace: {
            display: 0,
            display_name: "Publication Place",
            machine_name: "pubplace",
            placeholder: "Place of Publication e.g. New York",
            tooltip: "Place of Publication e.g. New York",
            required: 0

        },
        pubdate: {
            display: 0,
            display_name: "Publication Date",
            machine_name: "pubdate",
            placeholder: "Date (yyyy) of Publication",
            tooltip: "Date (yyyy) of Publication",
            required: 0

        },
        pubmedid: {
            display: 0,
            display_name: "PubMed ID",
            machine_name: "pmid",
            placeholder: "PubMed ID",
            tooltip: "PubMed ID",
            required: 0

        },
        purl: {
            display: 0,
            display_name: "PURL",
            machine_name: "puri",
            placeholder: "Permanent URL From UBC Library Catalogue",
            tooltip: "Permanent URL From UBC Library Catalogue",
            required: 0

        },
        isbn: {
            display: 0,
            display_name: "ISBN",
            machine_name: "isxn",
            placeholder: "ISBN",
            tooltip: "ISBN",
            required: 0

        },
        callnumber: {
            display: 0,
            display_name: "LC Call Number",
            machine_name: "callnumber",
            placeholder: "e.g B23.521",
            tooltip: "e.g B23.521",
            required: 0

        },
        uri: {
            display: 0,
            display_name: "URL",
            machine_name: "uri",
            placeholder: "URL",
            tooltip: "URL",
            required: 0

        },
        file: {
            display: 0,
            display_name: "File Upload",
            machine_name: "uploadfile",
            placeholder: "Select file to upload...",
            required: 0

        },
        notes: {
            display: 1,
            display_name: "Note to Students",
            machine_name: "note_student",
            placeholder: "are there any instructions to students?",
            tooltip: "Please insert any instructions for students...",
            required: 0

        },
        staffnotes: {
            display: 1,
            display_name: "Processing Notes",
            machine_name: "note_staff",
            placeholder: "please insert any processing notes for staff here",
            tooltip: "Please insert any processing notes for staff...",
            required: 0

        },
        tags: {
            display: 1,
            display_name: "Tags",
            machine_name: "tags",
            placeholder: "please, use, comma, sep, tags, plz, thnx, *twirl*",
            tooltip: "please, use, comma, sep, tags, plz, thnx, *twirl*",
            required: 0

        }
    };

    this.html = '<div class="content">';
    //console.log(options.fields);

    //form has default fields to display, and we enable specific fields to display
    for (var key in options.fields) {
        if (this.fields.hasOwnProperty(key)) {
            this.fields[key].display = 1;
            //TODO - toggle to enable/disable
            //console.log('Found key: '+key);
        }
    }

    //console.log('starting form');

    this.locr_type = options.values['locr_type'];
    this.submittype = options.values['formtype'];

    var skip = (this.submittype=='chapter');
    alert(skip);

    if(options.values['ignore'] ==  true ){
        //never comes here anymore
        for (var key in this.fields) {
            if (this.fields[key].display === 1) {
                this.html = this.html.concat('<div class="row-fluid"><div class="span12"><div class="input-prepend"><span class="add-on span2">'+this.fields[key].display_name+'</span><input class="span10 stored" type="text" id="ubc_id_'+this.fields[key].machine_name+'" name="'+this.fields[key].machine_name+'" value="" placeholder="'+(this.fields[key].placeholder ? this.fields[key].placeholder : "")+'"></div></div></div>');
            }
            else {
                //console.log("Skipping::  "+key+":"+options.values[key]);
            }
        }
    }
    else {
        for (var key in this.fields) {
            if (key === 'notes'){
                this.html = this.html.concat('<hr>');
            }
            if (this.fields[key].display === 1  && key != 'file') {
                if(skip){
                    this.html = this.html.concat('<div class="row-fluid"><div class="span12"><div class="input-prepend"><span class="add-on span2">'+this.fields[key].display_name+'</span><input class="span10 stored" type="text" id="ubc_id_'+this.fields[key].machine_name+'" name="'+this.fields[key].machine_name+'" value="'+(options.values[key] ? options.values[key] : "").toString().replace(/\\x22/g, '\'')+'" placeholder="'+(this.fields[key].placeholder ? this.fields[key].placeholder : "")+'" title="'+(this.fields[key].tooltip ? this.fields[key].tooltip : "")+'"></div></div></div>');
                }
                else {
                    this.html = this.html.concat('<div class="row-fluid"><div class="span12"><div class="input-prepend"><span class="add-on span2">'+this.fields[key].display_name+'</span><input class="span10 stored" type="text" id="ubc_id_'+this.fields[key].machine_name+'" name="'+this.fields[key].machine_name+'" value="'+(options.values[key] ? options.values[key] : "").toString().replace(/\\x22/g, '\'')+'" placeholder="'+(this.fields[key].placeholder ? this.fields[key].placeholder : "")+'" title="'+(this.fields[key].tooltip ? this.fields[key].tooltip : "")+'" '+(this.fields[key].onkeyup ? "onkeyup=\""+this.fields[key].onkeyup+"()\"" : "")+'></div></div></div>');
                }
            }
            else if(this.fields[key].display === 1  && key == 'file') {
                this.html = this.html.concat('<div class="row-fluid"><div class="span12"><div class="input-prepend"><span class="add-on span2">'+this.fields[key].display_name+'</span><input class="span10 stored" type="file" id="ubc_id_'+this.fields[key].machine_name+'" name="'+this.fields[key].machine_name+'" value="'+(options.values[key] ? options.values[key] : "")+'" placeholder="'+(this.fields[key].placeholder ? this.fields[key].placeholder : "")+'"></div></div></div>');
            }
            else {

            }
        }
    }

    this.html = this.html.concat('<div class="row-fluid"><div class="span12"><div class="input-prepend"><span class="add-on span2">Start Date</span><input class="span4 stored" type="text" id="ubc_id_start_date" name="startdate" value=""></div><div class="input-prepend"><span class="add-on span2">End Date</span><input class="span4 stored" type="text" id="ubc_id_end_date" name="enddate" value=""></div></div></div>');
    this.html = this.html.concat('<div class="row-fluid"><div class="span12"><div class="input-prepend"><span class="add-on span2">Required Reading</span><input class="" type="checkbox" id="ubc_id_required_reading" name="required_reading" value=""><span class="prepend-text">Select if this is a required reading for students</span></div></div></div>');



    //exclude loan period from certain types

    //TODO - goshhhh make decorators!!
    //NOTE - must also cater for this at requestItem below !!
    if(this.locr_type == "Electronic Article" || this.locr_type == "PDF" || this.locr_type == "eBook" || this.locr_type == "Streaming Media"){} else { this.html = this.html.concat('<div class="row-fluid"><div class="span12"><div class="input-prepend"><span class="add-on span2">Loan Period</span><select class="span4 stored" id="ubc_id_loanperiod" name="loanperiod"><option value="2 hours">2 Hours</option><option value="1 day">1 Day</option><option value="3 days">3 Days</option><option value="7 days">7 Days</option><option value="14 days">14 Days</option></select></div></div></div>');}

    if(this.locr_type == "Physical"){
        this.html = this.html.concat('<div class="row-fluid"><div class="span12"><div class="input-prepend"><span class="add-on span2">Instructor will <br>Provide Copy</span><input class="" type="checkbox" id="ubc_id_instructor_provided" name="instructor_provided" value=""><span class="prepend-text">Select if you will be providing library staff with the physical item</span></div></div></div>');
    } else { }

    this.html = this.html.concat('</div>');
    //when form is created, check to see if formtype != --- else the form is forced to not submit


    this.summonObject   = {};
    this.availabilityid = (options.values['availabilityid'] ? options.values['availabilityid'] : "");
}

//Form Factory  ----------------------------------------
// Define a skeleton factory
function FormFactory() { "use strict"; }

// Define the prototypes and utilities for this factory

// Our default formType is PreForm
FormFactory.prototype.formClass = PreForm;

// Our Factory method for creating new Form instances
FormFactory.prototype.createForm = function (options) {
    "use strict";
    if (options.formType === "presubmit") {
        this.formClass = PreForm;
    } else {
        this.formClass = SubmitForm;
    }

    return new this.formClass(options);

};

// Create an instance of our factory that makes cars
var factory = new FormFactory();

function showDirectSubmitForm(fields,locrType,referrer){

    //Almost Identical to showSubmitForm, but kept separate in case we need to customise this further
    var form = new factory.createForm({
        fields: fields,
        values: {locr_type: locrType, formtype: referrer}
    });
    finalSubmitForm(form,referrer);
}

function finalSubmitForm(form,referrer){
    var data = {};

    if(referrer == 'manual' && form.locr_type == 'Electronic Article'){
        form.locr_type = 'Format Unknown';
    }

    $('#submit-form').html(form.html);

    $('#submit-item').reveal({
        animation: 'fade',
        animationspeed: 50,
        closeonbackgroundclick: false,
        dismissmodalclass: 'close-reveal-modal',
        open: function () {
            $('html,body').css('overflow','hidden');
            $('#ubc_id_start_date').datepicker({
                dateFormat : 'yy-mm-dd'
            });
            var dateParts = querySDate.match(/(\d+)/g);
            var realDate = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
            $('#ubc_id_start_date').datepicker("setDate",realDate);
            $('#ubc_id_end_date').datepicker({
                dateFormat : 'yy-mm-dd'
            });

            dateParts = queryEDate.match(/(\d+)/g);
            realDate = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
            $('#ubc_id_end_date').datepicker("setDate",realDate);

            $('#submit-form input[title]').qtip({
                show: 'focus',
                hide: 'blur',
                position: {
                    my: 'bottom left',  // Position my top left...
                    at: 'top left' // at the bottom right of...
                },
                style: {
                    classes: 'qtip-dark qtip-shadow'
                }
            });
            $('#submit-item-submit-btn').text('Submit Item').removeAttr('disabled');
        },
        opened: function () {

            $('#submit-form').perfectScrollbar({
                wheelSpeed: 50,
                wheelPropagation: true,
                suppressScrollX: true
            });
            $('#submit-item-submit-btn').on('click', function(){
                var uri = ($('#ubc_id_uri').length ? $('#ubc_id_uri').val() : '');
                var callnumber = ($('#ubc_id_callnumber').length ? $('#ubc_id_callnumber').val() : '');

                for (var key in form.fields) {
                    if(form.fields[key].display === 1){
                        var this_id = '#submit-form #ubc_id_'+form.fields[key].machine_name;
                        if ($(this_id).length) {
                            data[form.fields[key].machine_name] = $(this_id).val();
                        }
                    }
                }

                if(typeof form.fields != 'undefined'){

                    data['locr_type']       = form.locr_type;
                    data['submit_type']     = form.submittype;
                    data['availabilityid']  = form.availabilityid;
                    data['summon_blob']     = form.summonObject;

                    //console.log("Form Data:");
                    //console.log(data);

                    var skipCheck = !!(form.locr_type === 'Physical' && form.submittype === '---');

                    var loanperiod = (form.locr_type == "Electronic Article" || form.locr_type == "PDF" || form.locr_type == "eBook" || form.locr_type == "Streaming Media" || form.locr_type == "Format Unknown" ? "N/A" : $('#ubc_id_loanperiod').val());

                    var instanceid;
                    var _itemid = createItem(form,data);


                    if(_itemid < 0){
                        alert('An error occurred whilst creating item');
                    }
                    else {
                        instanceid = requestItem(course_id,_itemid,loanperiod,puid);


                        //should add a callback in request item to continue onwards
                        while (undefined == instanceid || void 0 == instanceid){};

                        //set flag if the reading is required
                        if($('#ubc_id_required_reading').prop('checked')){
                            var _setRequired = setCourseItemCommand(course_id,_itemid,'SetCIRequired',1);
                            //alert(_setRequired);
                        }

                        //set status if the physical item will be provided
                        if($('#ubc_id_instructor_provided').prop('checked')){
                            var _setRequired = setCourseItemCommand(course_id,_itemid,'SetCIStatus',19);
                            alert(_setRequired);
                        }

                        //set flag if there are notes to staff
                        if($('#ubc_id_note_staff').val().length > 0 ){
                            var _setNote = addCINote(puid,course_id,_itemid,JSON.stringify($('#ubc_id_note_staff').val()),JSON.stringify('6,7'));
                            //alert(_setNote);
                        }

                        //set flag if there are notes to staff
                        if($('#ubc_id_note_student').val().length > 0 ){
                            var _setNote = addCINote(puid,course_id,_itemid,JSON.stringify($('#ubc_id_note_student').val()),JSON.stringify('9'));
                            //alert(_setNote);
                        }

                        if (referrer == 'docstore'){
                            //console.log('post to docstore here');

                            var fd = new FormData();
                            fd.append("actionlabel",'docstore-submission');
                            fd.append('course_id', course_id);
                            fd.append('item_id', _itemid);
                            fd.append('puid', puid);
                            fd.append('initiator', 'connect');
                            fd.append("uploadfile", $("#ubc_id_uploadfile")[0].files[0]);

                            //TODO add docstore to config and change below url to read from config

                            $.ajax({
                                url: "{{base_url}}/docstore.create",
                                type: "POST",
                                data: fd,
                                processData: false,
                                contentType: false,
                                dataType: 'json'
                            }).done(function(docstorehash) {

                                }).fail(function() {
                                    alert('failed');
                                }).always(function() {
                                    alert('always');
                                });
                        }
                    }

                    $('#submit-item > .close-reveal-modal').click();

                    $('#submit-results').reveal({
                        animation: 'fade',
                        animationspeed: 50,
                        closeonbackgroundclick: false,
                        dismissmodalclass: 'close-reveal-modal',
                        open: function () {
                            if(instanceid >= 0){
                                $('#submit-results-success').text("Success. Item requested with ItemID: "+_itemid);
                            }
                            else {
                                if (instanceid == -1){
                                    $('#submit-results-success').text("This item has already been requested for this course.");
                                }
                                else if (instanceid == -2) {
                                    $('#submit-results-success').text("The course reserves system (licr side) produced an error while retrieving the id.");
                                }
                                else if (instanceid == -9) {
                                    $('#submit-results-success').text("The course reserves system (connect side) produced an error while retrieving the id.");
                                }
                            }
                        }
                    });

                    delete form.fields;
                    delete form.html;
                    delete form.locr_type;
                    delete form.submittype;
                }
            });
        },
        close: function () {
            $('#submit-form').perfectScrollbar('destroy');
        },
        closed: function () {
            delete form.fields;
            delete form.html;
            delete form.locr_type;
            delete form.submittype;
            $('#submit-form').html('');
            $('html,body').css('overflow','initial');
        }
    });
}

function setCourseItemCommand(course,itemid,command,value){
    var _resp = -3;
    $.ajax({
        type: "POST",
        url: "/mediator."+command,
        data: { c:course, i:itemid, v:value },
        dataType: 'json',
        async: false
    })
        .done(function(response) {
            if (response === 'Item not found'){
                _resp = -1;
            }
            else {
                _resp = response;
            }
        })
        .fail(function(jqXHR,textStatus) {
            //console.log(textStatus);
            _resp = -2;
        });
    return _resp;
}

function addCINote(puid,course,itemid,content,roles){
    var _resp = -3;
    $.ajax({
        type: "POST",
        url: "/mediator.addCINote",
        data: { p: puid, c:course, i:itemid, v:content, r: roles },
        dataType: 'json',
        async: false
    })
        .done(function(response) {
            if (response === 'Item not found'){
                _resp = -1;
            }
            else {
                _resp = response;
            }
        })
        .fail(function(jqXHR,textStatus) {
            //console.log(textStatus);
            _resp = -2;
        });
    return _resp;
}

function setItemCommand(itemid,command,value){
    var _resp = -3;
    $.ajax({
        type: "POST",
        url: "/mediator."+command,
        data: { i:itemid, v:value },
        dataType: 'json',
        async: false
    })
        .done(function(response) {
            if (response === 'Item not found'){
                _resp = -1;
            }
            else {
                _resp = response;
            }
        })
        .fail(function(jqXHR,textStatus) {
            //console.log(textStatus);
            _resp = -2;
        });
    return _resp;
}

function createItem(formData,data){
    var _id = -3;
    $.ajax({
        type: "POST",
        url: "/mediator.addItem",
        async: false,
        data: {
            'title'             : data['articletitle'] ? data['articletitle'] : data['title']
            ,'callnumber'       : data['callnumber'] ? data['callnumber'] : ''
            ,'uri'              : data['uri'] ? data['uri'] : ''
            ,'type'             : formData.locr_type
            ,'filelocation'     : data['filelocation'] ? data['filelocation'] : '---'
            ,'citation'         : data['citation'] ? data['citation'] : '---'
            ,'external'         : data['external'] ? data['external'] : '---'
            ,'form'             : data
        },
        dataType: 'json'
    })
        .done(function(response) {
            //console.log(response);
            if(response == false){
                _id = -3;
            }
            else {
                _id = response;
            }
        })
        .fail(function(jqXHR,textStatus) {
            alert(textStatus);
            _id = -2;
        });
    return _id;
}

$('#submit-results-submit-btn').on('click', function(){
    $('#submit-results').trigger('reveal:close');
    location.reload();
});


function requestItem(course,itemid,loanperiod,user){

    var _instanceid = -10;

    console.log("---------------------------------   Request Object   ---------------------------------");
    console.log({CourseID: course,ItemID: itemid, LoanPeriod: loanperiod, User: user});
    console.log("--------------------------------------------------------------------------------------");

    $.ajax({
        type: "POST",
        url: "/mediator.requestItem",
        data: {
            'course'        : course
            ,'item_id'      : itemid
            ,'loanperiod'   : loanperiod
            ,'requestor'    : user
        },
        dataType: 'json',
        async: false
    })
        .done(function(response) {

            if(typeof response.instance_id != 'undefined'){
                _instanceid = response.instance_id;
            }
            else {
                if (response == 'CourseItem::request item already requested for this course'){
                    _instanceid = -1;
                }
                else {
                    _instanceid = -2;
                }
            }
        })
        .fail(function(jqXHR,textStatus) {
            alert(textStatus);
            _instanceid = -9;
        });
    //console.log(_instanceid);
    return _instanceid;
}






/*! perfect-scrollbar - v0.4.6
 * http://noraesae.github.com/perfect-scrollbar/
 * Copyright (c) 2013 HyeonJe Jun; Licensed MIT */
"use strict";(function(e){"function"==typeof define&&define.amd?define(["jquery"],e):e(jQuery)})(function(e){var r={wheelSpeed:10,wheelPropagation:!1,minScrollbarLength:null,useBothWheelAxes:!1,useKeyboard:!0,suppressScrollX:!1,suppressScrollY:!1,scrollXMarginOffset:0,scrollYMarginOffset:0};e.fn.perfectScrollbar=function(o,t){return this.each(function(){var l=e.extend(!0,{},r),n=e(this);if("object"==typeof o?e.extend(!0,l,o):t=o,"update"===t)return n.data("perfect-scrollbar-update")&&n.data("perfect-scrollbar-update")(),n;if("destroy"===t)return n.data("perfect-scrollbar-destroy")&&n.data("perfect-scrollbar-destroy")(),n;if(n.data("perfect-scrollbar"))return n.data("perfect-scrollbar");n.addClass("ps-container");var s,c,a,i,p,f,u,d,b,h,v=e("<div class='ps-scrollbar-x-rail'></div>").appendTo(n),g=e("<div class='ps-scrollbar-y-rail'></div>").appendTo(n),m=e("<div class='ps-scrollbar-x'></div>").appendTo(v),w=e("<div class='ps-scrollbar-y'></div>").appendTo(g),T=parseInt(v.css("bottom"),10),L=parseInt(g.css("right"),10),y=function(){var e=parseInt(h*(f-i)/(i-b),10);n.scrollTop(e),v.css({bottom:T-e})},S=function(){var e=parseInt(d*(p-a)/(a-u),10);n.scrollLeft(e),g.css({right:L-e})},I=function(e){return l.minScrollbarLength&&(e=Math.max(e,l.minScrollbarLength)),e},X=function(){v.css({left:n.scrollLeft(),bottom:T-n.scrollTop(),width:a,display:l.suppressScrollX?"none":"inherit"}),g.css({top:n.scrollTop(),right:L-n.scrollLeft(),height:i,display:l.suppressScrollY?"none":"inherit"}),m.css({left:d,width:u}),w.css({top:h,height:b})},D=function(){a=n.width(),i=n.height(),p=n.prop("scrollWidth"),f=n.prop("scrollHeight"),!l.suppressScrollX&&p>a+l.scrollXMarginOffset?(s=!0,u=I(parseInt(a*a/p,10)),d=parseInt(n.scrollLeft()*(a-u)/(p-a),10)):(s=!1,u=0,d=0,n.scrollLeft(0)),!l.suppressScrollY&&f>i+l.scrollYMarginOffset?(c=!0,b=I(parseInt(i*i/f,10)),h=parseInt(n.scrollTop()*(i-b)/(f-i),10)):(c=!1,b=0,h=0,n.scrollTop(0)),h>=i-b&&(h=i-b),d>=a-u&&(d=a-u),X()},Y=function(e,r){var o=e+r,t=a-u;d=0>o?0:o>t?t:o,v.css({left:n.scrollLeft()}),m.css({left:d})},x=function(e,r){var o=e+r,t=i-b;h=0>o?0:o>t?t:o,g.css({top:n.scrollTop()}),w.css({top:h})},C=function(){var r,o;m.bind("mousedown.perfect-scrollbar",function(e){o=e.pageX,r=m.position().left,v.addClass("in-scrolling"),e.stopPropagation(),e.preventDefault()}),e(document).bind("mousemove.perfect-scrollbar",function(e){v.hasClass("in-scrolling")&&(S(),Y(r,e.pageX-o),e.stopPropagation(),e.preventDefault())}),e(document).bind("mouseup.perfect-scrollbar",function(){v.hasClass("in-scrolling")&&v.removeClass("in-scrolling")}),r=o=null},P=function(){var r,o;w.bind("mousedown.perfect-scrollbar",function(e){o=e.pageY,r=w.position().top,g.addClass("in-scrolling"),e.stopPropagation(),e.preventDefault()}),e(document).bind("mousemove.perfect-scrollbar",function(e){g.hasClass("in-scrolling")&&(y(),x(r,e.pageY-o),e.stopPropagation(),e.preventDefault())}),e(document).bind("mouseup.perfect-scrollbar",function(){g.hasClass("in-scrolling")&&g.removeClass("in-scrolling")}),r=o=null},k=function(){var e=function(e,r){var o=n.scrollTop();if(0===o&&r>0&&0===e)return!l.wheelPropagation;if(o>=f-i&&0>r&&0===e)return!l.wheelPropagation;var t=n.scrollLeft();return 0===t&&0>e&&0===r?!l.wheelPropagation:t>=p-a&&e>0&&0===r?!l.wheelPropagation:!0},r=!1;n.bind("mousewheel.perfect-scrollbar",function(o,t,a,i){l.useBothWheelAxes?c&&!s?i?n.scrollTop(n.scrollTop()-i*l.wheelSpeed):n.scrollTop(n.scrollTop()+a*l.wheelSpeed):s&&!c&&(a?n.scrollLeft(n.scrollLeft()+a*l.wheelSpeed):n.scrollLeft(n.scrollLeft()-i*l.wheelSpeed)):(n.scrollTop(n.scrollTop()-i*l.wheelSpeed),n.scrollLeft(n.scrollLeft()+a*l.wheelSpeed)),D(),r=e(a,i),r&&o.preventDefault()}),n.bind("MozMousePixelScroll.perfect-scrollbar",function(e){r&&e.preventDefault()})},M=function(){var r=function(e,r){var o=n.scrollTop();if(0===o&&r>0&&0===e)return!1;if(o>=f-i&&0>r&&0===e)return!1;var t=n.scrollLeft();return 0===t&&0>e&&0===r?!1:t>=p-a&&e>0&&0===r?!1:!0},o=!1;n.bind("mouseenter.perfect-scrollbar",function(){o=!0}),n.bind("mouseleave.perfect-scrollbar",function(){o=!1});var t=!1;e(document).bind("keydown.perfect-scrollbar",function(e){if(o){var s=0,c=0;switch(e.which){case 37:s=-3;break;case 38:c=3;break;case 39:s=3;break;case 40:c=-3;break;default:return}n.scrollTop(n.scrollTop()-c*l.wheelSpeed),n.scrollLeft(n.scrollLeft()+s*l.wheelSpeed),D(),t=r(s,c),t&&e.preventDefault()}})},O=function(){var e=function(e){e.stopPropagation()};w.bind("click.perfect-scrollbar",e),g.bind("click.perfect-scrollbar",function(e){var r=parseInt(b/2,10),o=e.pageY-g.offset().top-r,t=i-b,l=o/t;0>l?l=0:l>1&&(l=1),n.scrollTop((f-i)*l),D()}),m.bind("click.perfect-scrollbar",e),v.bind("click.perfect-scrollbar",function(e){var r=parseInt(u/2,10),o=e.pageX-v.offset().left-r,t=a-u,l=o/t;0>l?l=0:l>1&&(l=1),n.scrollLeft((p-a)*l),D()})},E=function(){var r=function(e,r){n.scrollTop(n.scrollTop()-r),n.scrollLeft(n.scrollLeft()-e),D()},o={},t=0,l={},s=null,c=!1;e(window).bind("touchstart.perfect-scrollbar",function(){c=!0}),e(window).bind("touchend.perfect-scrollbar",function(){c=!1}),n.bind("touchstart.perfect-scrollbar",function(e){var r=e.originalEvent.targetTouches[0];o.pageX=r.pageX,o.pageY=r.pageY,t=(new Date).getTime(),null!==s&&clearInterval(s),e.stopPropagation()}),n.bind("touchmove.perfect-scrollbar",function(e){if(!c&&1===e.originalEvent.targetTouches.length){var n=e.originalEvent.targetTouches[0],s={};s.pageX=n.pageX,s.pageY=n.pageY;var a=s.pageX-o.pageX,i=s.pageY-o.pageY;r(a,i),o=s;var p=(new Date).getTime();l.x=a/(p-t),l.y=i/(p-t),t=p,e.preventDefault()}}),n.bind("touchend.perfect-scrollbar",function(){clearInterval(s),s=setInterval(function(){return.01>Math.abs(l.x)&&.01>Math.abs(l.y)?(clearInterval(s),void 0):(r(30*l.x,30*l.y),l.x*=.8,l.y*=.8,void 0)},10)})},A=function(){n.unbind(".perfect-scrollbar"),e(window).unbind(".perfect-scrollbar"),e(document).unbind(".perfect-scrollbar"),n.data("perfect-scrollbar",null),n.data("perfect-scrollbar-update",null),n.data("perfect-scrollbar-destroy",null),m.remove(),w.remove(),v.remove(),g.remove(),m=w=a=i=p=f=u=d=T=b=h=L=null},j=function(r){n.addClass("ie").addClass("ie"+r);var o=function(){var r=function(){e(this).addClass("hover")},o=function(){e(this).removeClass("hover")};n.bind("mouseenter.perfect-scrollbar",r).bind("mouseleave.perfect-scrollbar",o),v.bind("mouseenter.perfect-scrollbar",r).bind("mouseleave.perfect-scrollbar",o),g.bind("mouseenter.perfect-scrollbar",r).bind("mouseleave.perfect-scrollbar",o),m.bind("mouseenter.perfect-scrollbar",r).bind("mouseleave.perfect-scrollbar",o),w.bind("mouseenter.perfect-scrollbar",r).bind("mouseleave.perfect-scrollbar",o)},t=function(){X=function(){m.css({left:d+n.scrollLeft(),bottom:T,width:u}),w.css({top:h+n.scrollTop(),right:L,height:b}),m.hide().show(),w.hide().show()},y=function(){var e=parseInt(h*f/i,10);n.scrollTop(e),m.css({bottom:T}),m.hide().show()},S=function(){var e=parseInt(d*p/a,10);n.scrollLeft(e),w.hide().show()}};6===r&&(o(),t())},W="ontouchstart"in window||window.DocumentTouch&&document instanceof window.DocumentTouch,H=function(){var e=navigator.userAgent.toLowerCase().match(/(msie) ([\w.]+)/);e&&"msie"===e[1]&&j(parseInt(e[2],10)),D(),C(),P(),O(),W&&E(),n.mousewheel&&k(),l.useKeyboard&&M(),n.data("perfect-scrollbar",n),n.data("perfect-scrollbar-update",D),n.data("perfect-scrollbar-destroy",A)};return H(),n})}}),function(e){function r(r){var o=r||window.event,t=[].slice.call(arguments,1),l=0,n=0,s=0;return r=e.event.fix(o),r.type="mousewheel",o.wheelDelta&&(l=o.wheelDelta/120),o.detail&&(l=-o.detail/3),s=l,void 0!==o.axis&&o.axis===o.HORIZONTAL_AXIS&&(s=0,n=-1*l),void 0!==o.wheelDeltaY&&(s=o.wheelDeltaY/120),void 0!==o.wheelDeltaX&&(n=-1*o.wheelDeltaX/120),t.unshift(r,l,n,s),(e.event.dispatch||e.event.handle).apply(this,t)}var o=["DOMMouseScroll","mousewheel"];if(e.event.fixHooks)for(var t=o.length;t;)e.event.fixHooks[o[--t]]=e.event.mouseHooks;e.event.special.mousewheel={setup:function(){if(this.addEventListener)for(var e=o.length;e;)this.addEventListener(o[--e],r,!1);else this.onmousewheel=r},teardown:function(){if(this.removeEventListener)for(var e=o.length;e;)this.removeEventListener(o[--e],r,!1);else this.onmousewheel=null}},e.fn.extend({mousewheel:function(e){return e?this.bind("mousewheel",e):this.trigger("mousewheel")},unmousewheel:function(e){return this.unbind("mousewheel",e)}})}(jQuery);
