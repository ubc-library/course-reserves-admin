$.curCSS = $.css;
if (pageLevel == 'top') {
    $(document).on('click', '.prgedit', function () {
        var url = '/program.courses/edit/' + $(this).val();
        document.location = url;
        return false;
    });
    $('#addprogram').click(
        function () {
            var addProgramFailed = $('#addProgramFailed');
            var prgname = $('#addprogram_name').val();
            var prgyear = $('#addprogram_gradyear').val();
            if (!prgname) {
                displayNotice(addProgramFailed, "<strong>Error:</strong> <br/>No program name was specified!");
                return false;
            }
            if (parseInt(prgyear, 10) < 2000) {
                displayNotice(addProgramFailed, "<strong>Error:</strong> <br/>The graduation year is invalid!");
                return false;
            } else {
                prgyear = parseInt(prgyear, 10);
            }
            $.ajax({
                    type: "POST",
                    url: "/program.create",
                    data: {
                        name: prgname,
                        year: prgyear
                    },
                    dataType: 'json',
                    async: false
                })
                .done(
                    function (program_id) {
                        if (!program_id) {
                            displayNotice(addProgramFailed, "<strong>Error:</strong> <br/>Failed to create program!");
                            return false;
                        } else {
                            var html = '<tr id="prgtr' + program_id + '">'
                                + '<td>'
                                + prgname
                                + '</td>'
                                + '<td>'
                                + prgyear
                                + '</td>'
                                + '<td>'
                                + '<button class="btn prgedit" value="' + program_id + '">Edit</button> '
                                + '<button class="btn prgdelete" value="' + program_id + '">Delete</button>'
                                + '</td>' + '</tr>';
                            $('#prgtb').append(html);
                            return true;
                        }
                    })
                .error(
                    function () {
                        displayNotice(addProgramFailed, "<strong>Error:</strong> <br/>Program name and graduation year already exists!");
                    }
                );
        });
    $(document).on('click', '.prgdelete', function () {
        if (!confirm('Delete program: Are you sure?')) {
            return false;
        }
        var prgid = $(this).val();
        $.ajax({
            type: "POST",
            url: "/program.delete",
            data: {
                program: prgid
            },
            dataType: 'json',
            async: false
        }).done(function (success) {
            if (!success) {
                displayNotice($('#programFailed'), "<strong>Error:</strong> <br/>Failed to delete program!");
                return false;
            } else {
                $('#prgtr' + prgid).remove();
                return true;
            }
        });
    });
}
else if (pageLevel == 'program') {
//simple autocomplete wants [{id:,label:,value:}]
    $('#addcourse_id').autocomplete({
        appendTo: "#crssearchres",
        source: function (request, response) {
            var term = request.term;
            $.ajax({
                type: "POST",
                url: "/program.coursesearch",
                data: {
                    partial: term
                },
                dataType: 'json',
                async: false
            }).done(function (data) {
                response(data);
                return true;
            });
        },
        minLength: 2,
        select: function (event, ui) {
            _addcourse(ui);
            setTimeout(function () {
                $('#addcourse_id').val('');
            }, 100);
        }
    });

    function _addcourse(ui) {
        var course_id = ui.item.id;
        var addCourseFailed = $('#addCourseFailed');
        if ($('#crstr' + course_id).size() != 0) {
            displayNotice(addCourseFailed, "<strong>Error:</strong> <br/>This course is already part of this program!");
            return false;
        }
        $.ajax({
                type: "POST",
                url: "/program.add_course",
                data: {
                    program: programId,
                    course: course_id
                },
                dataType: 'json',
                async: false
            })
            .done(function (success) {
                if (!success) {
                    displayNotice(addCourseFailed, "<strong>Error:</strong> <br/>Failed to add course to program!");
                    return false;
                } else {
                    var html = '<tr id="crstr' + course_id + '">'
                        + '<td>' + course_id + '</td>'
                        + '<td>'
                        + ui.item.label
                        + '</td>'
                        + '<td>'
                        + '<button class="btn rmcourse" value="' + course_id + '">Remove</button>'
                        + '</td>' + '</tr>';
                    $('#crstb').append(html);
                    return true;
                }
            });
    }

    $(document).on('click', '.updateProgram', function (e) {
        e.preventDefault();
        var prgmid = $(this).val();
        var prgname = $('#update_program_title').val();
        var prgyear = $('#update_graduation_year').val();
        var updateProgramFailed = $('#updateProgramFailed');
        if (!prgname) {
            displayNotice(updateProgramFailed, "<strong>Error:</strong> <br/>No program name was specified!");
            return false;
        }
        if (parseInt(prgyear, 10) < 2000) {
            displayNotice(updateProgramFailed, "<strong>Error:</strong> <br/>Graduation year is invalid!");
            return false;
        } else {
            prgyear = parseInt(prgyear, 10);
        }

        $.ajax({
                type: "POST",
                url: "/program.update",
                data: {
                    id: prgmid,
                    name: prgname,
                    year: prgyear
                },
                dataType: 'json',
                async: false
            })
            .done(
                function (success) {
                    if (!success) {
                        displayNotice(updateProgramFailed, "<strong>Error:</strong> <br/>Failed to update program!");
                        return false;
                    } else {
                        var updateProgramSuccess = $('#updateProgramSuccess');
                        displayNotice(updateProgramSuccess, "<strong>Success:</strong><br/>Program successfully updated!");
                        $('h3#programTitle').html("Courses in Program &ldquo;" + prgname + "&rdquo;[" + prgyear + "]");
                        return true;
                    }
                })
            .error(
                function () {
                    displayNotice(updateProgramFailed, "<strong>Error:</strong> <br/>Program title and graduation year already exists!");
                }
            );
    });

    $(document).on('click', '.rmcourse', function () {
        var course_id = $(this).val();
        $.ajax({
            type: "POST",
            url: "/program.remove_course",
            data: {
                program: programId,
                course: course_id
            },
            dataType: 'json',
            async: false
        }).done(function (success) {
            if (!success) {
                displayNotice($('#programFailed'), "<strong>Error:</strong> <br/>Failed to remove course!");
                return false;
            } else {
                $('#crstr' + course_id).remove();
                return true;
            }
        });
    });
}