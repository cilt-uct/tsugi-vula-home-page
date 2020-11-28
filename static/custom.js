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
    $('#uploadModal #file_created, #file_expiry').val(maxDate);

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

    $('#uploadModal').on('hidden.bs.modal', function () {
        $('#uploadModal input[type=text], input[type=file], input[type=url], textarea').val("");
        $('#uploadModal #file_category').val("");
        $('#uploadModal #file_created').val(maxDate);
        $('#uploadModal #file_expiry').val(maxDate);
    });

    $('#uploadModal').on('shown.bs.modal', function () {
        $('#uploadModal #file_created').val(maxDate);
        $('#uploadModal #file_expiry').attr('min', maxDate);
        $('#uploadModal #file_created').attr('min', maxDate);
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

    $('#imageModal').on('shown.bs.modal', function (e) {
        var clickedImage = e.relatedTarget.getAttribute('data-url');
        $('#imageModal .modal-content img').attr('src',clickedImage);
        $('#imageModal').modal();
    });

    $('#uploadModal').on('click', '#mark_archive', function(e) {
        $("[name='fileStatus']").prop("checked", false);
        $(this).prop("checked", true);
    });

    $('#deleteModal').on('shown.bs.modal', function (e) {
        var clickedFile = e.relatedTarget.getAttribute('data-file');
        $('#deleteModal .modal-header h4').html("Delete " + clickedFile);
        $('#deleteModal #file_name').val(clickedFile);
        $('#deleteModal').modal();
    });

    $('#deleteModal').on('click', '#deleteFileBtn', function(e) {
        $('#deleteModal').modal('hide');
    });

    /*
    this gets the data for filename from data array
    getObj("85724.jpg", data, 'filename')*/
});


function filterTable() {
    $('#table-info-body tr').show();
    makePagination();
}

function getPageList(totalPages, page, maxLength) {
    if (maxLength < 5) throw "maxLength must be at least 5";

    function range(start, end) {
        return Array.from(Array(end - start + 1), (_, i) => i + start); 
    }

    var sideWidth = maxLength < 9 ? 1 : 2;
    var leftWidth = (maxLength - sideWidth*2 - 3) >> 1;
    var rightWidth = (maxLength - sideWidth*2 - 2) >> 1;
    if (totalPages <= maxLength) {
       
        return range(1, totalPages);
    }
    if (page <= maxLength - sideWidth - 1 - rightWidth) {
        
        return range(1, maxLength - sideWidth - 1)
            .concat(0, range(totalPages - sideWidth + 1, totalPages));
    }
    if (page >= totalPages - sideWidth - 1 - rightWidth) {
        
        return range(1, sideWidth)
            .concat(0, range(totalPages - sideWidth - 1 - rightWidth - leftWidth, totalPages));
    }
   
    return range(1, sideWidth)
        .concat(0, range(page - leftWidth, page + rightWidth),
                0, range(totalPages - sideWidth + 1, totalPages));
}

function makePagination() {
    var numberOfItems = $('#table-info').attr('data-total');
    var limitPerPage = $('#table-info').attr('data-limit');
    var totalPages = Math.ceil(numberOfItems / limitPerPage);
    var paginationSize = 7; 
    var currentPage;
    $("#table-info-body").hide();


    function showPage(whichPage) {
        if (whichPage < 1 || whichPage > totalPages) return false;
        currentPage = whichPage;
        $("#table-info-body tr").hide()
            .slice((currentPage-1) * limitPerPage, 
                    currentPage * limitPerPage).show();       
        // Replace the navigation items (not prev/next):            
        $(".pagination li").slice(1, -1).remove();
        getPageList(totalPages, currentPage, paginationSize).forEach( item => {
            $("<li>").addClass("page-item")
                    .addClass(item ? "current-page" : "disabled")
                    .toggleClass("active", item === currentPage).append(
                $("<a>").addClass("page-link").attr({
                    href: "javascript:void(0)"}).text(item || "...")
            ).insertBefore("#next-page");
        });
        // Disable prev/next when at first/last page:
        $("#previous-page").toggleClass("disabled", currentPage === 1);
        $("#next-page").toggleClass("disabled", currentPage === totalPages);
        return true;
    }

    $(".pagination").append(
        $("<li>").addClass("page-item").attr({ id: "previous-page" }).append(
            $("<a>").addClass("page-link").attr({
                href: "javascript:void(0)"}).text("Prev")
        ),
        $("<li>").addClass("page-item").attr({ id: "next-page" }).append(
            $("<a>").addClass("page-link").attr({
                href: "javascript:void(0)"}).text("Next")
        )
    );
  
    $("#table-info-body").show();
    showPage(1);

    $(document).on("click", ".pagination li.current-page:not(.active)", function () {
        return showPage(+$(this).text());
    });
    $("#next-page").on("click", function () {
        return showPage(currentPage+1);
    });

    $("#previous-page").on("click", function () {
        return showPage(currentPage-1);
    });
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
