function getObj(id, arr, key) { key = key || 'id'; var o = null; $.each(arr, function (i, el) { if (el[key] == id) { o=el; return; } }); return o; };

$(document).ready(function () {
    var dtToday = new Date(),
        month = dtToday.getMonth() + 1,
        day = dtToday.getDate(),
        year = dtToday.getFullYear();

    if(month < 10) {
        month = '0' + month.toString();
    }

    if(day < 10) {
        day = '0' + day.toString();
    }
    
    var maxDate = year + '-' + month + '-' + day;

    $('.table th.sortable').on('click', function (event) {
        var idx = $(this).index(),
            row = $(this).parent(),
            tbl = row.parent().parent();

        if ($(this).hasClass('asc')) {
            $(this).addClass('desc').removeClass('asc');
        } else if ($(this).hasClass('desc')) {
            $(this).addClass('asc').removeClass('desc');
        } else {
            row.children('th').removeClass('asc desc');
            $(this).addClass('desc');
        }
        sortTable(tbl, idx, $(this).hasClass('asc') ? -1 : 1, ($(this).attr('rel') === 'number'));
    });

    $('#imageModal').on('shown.bs.modal', function (e) {
        var clickedImage = e.relatedTarget.getAttribute('data-url');
        $('#imageModal .modal-content img').attr('src',clickedImage);
        $('#imageModal').modal();
    });

    $('#editModal').on('shown.bs.modal', function (e) {
        var fileName = e.relatedTarget.getAttribute('data-file'),
            fileDetails = getObj(fileName, data, 'filename');
        
        $('#editModal .modal-header h4').html("Edit " + fileName);
        $('#editModal #file_submitter').val(fileDetails.submitter);
        $('#editModal #submitter_email').val(fileDetails.submitterEmail);
        $('#editModal #jira_issue').val(fileDetails.jiraIssue);
        $('#editModal #file_category').val(fileDetails.category);
        $('#editModal #file_name').val(fileName);
        $('#editModal #file_expiry').attr('min', fileDetails.expires);
        $('#editModal #file_expiry').val(fileDetails.expires);
        $('#editModal #file_url').val(fileDetails.url);
        $('#editModal #file_comments').val(fileDetails.comments);
      
        if(fileDetails.fileStatus == 'Archive') {
            $('#editModal #mark_archive').prop('checked', true);
        } else if($('#editModal #file_expiry').val() >= maxDate) {
            $('#editModal #mark_status').text('Active');
            $('#editModal #mark_active').prop('checked', true);
        } else {
            $('#editModal #mark_status').text('Inactive');
            $('#editModal #mark_active').prop('checked', true);
        }
    });

    $('#editModal').on('hidden.bs.modal', function () {
        $('#editModal .modal-header h4').html("");
        $('#editModal input[type=text], textarea').val("");
        $('#editModal input[type=radio]').prop('checked', false);
    });

    $('#archiveModal').on('shown.bs.modal', function (e) {
        var clickedFile = e.relatedTarget.getAttribute('data-file');
        var fileExpiry = e.relatedTarget.getAttribute('data-expires');
        $('#archiveModal .modal-header h4').html("Archive " + clickedFile);
        $('#archiveModal #file_name').val(clickedFile);
        $('#archiveModal #file_expiry').val(fileExpiry);
        $('#archiveModal').modal();
        console.log(e.relatedTarget);
    });

    $('#archiveModal').on('click', '#archiveFileBtn', function(e) {
        $('#archiveModal').modal('hide');
    });

});

function filterTable() {
    $('#table-info-body tr').show();
}

// get form inputs
function getInputs(_form) {
    _form = _form || $('.modal.in form')[0];
    if (!_form) {
        return null;
    }

    var changes = $(_form).find('input[name],select[name],textarea[name]').toArray().reduce(function(result, input) {
    if (input.name.indexOf('[]') > -1) {
        if (!result[input.name]) {
        result[input.name] = [];
        }
        if ((input.type === 'checkbox' && input.checked) || input.type !== 'checkbox') {
        result[input.name].push(input.value);
        }
    }
    else if (['checkbox', 'radio'].indexOf(input.type) > -1) {
        if ($(_form).find('input[name="' + input.name + '"]').length === 1) {
        result[input.name] = input.checked;
        }
    }
    else if (input.type === 'file') {
        result[input.name] = input.files[0];
    }
    else {
        result[input.name] = input.value;
    }
    return result;
    }, {});

    return changes;
}

function sortTable(tbl, idx, dir, n) {
    var rows = tbl.find('tbody>tr').get();
    dir = n ? -1*dir : dir;
    
    rows.sort(function (a, b) {
        var A = n ? parseFloat($(a).find('td').eq(idx).text().replace(/[A-Z]|[a-z]|\,/gi,"")) : $(a).find('td').eq(idx).text().toLowerCase();
        var B = n ? parseFloat($(b).find('td').eq(idx).text().replace(/[A-Z]|[a-z]|\,/gi,"")) : $(b).find('td').eq(idx).text().toLowerCase();

        if (A < B) {
            return -1 * dir;
        }
        if (A > B) {
            return 1 * dir;
        }
        return 0;
    });

    $.each(rows, function (index, row) {
        tbl.find('tbody').append(row);
    });
}
