function getObj(id, arr, key) { key = key || 'id'; var o = null; $.each(arr, function (i, el) { if (el[key] == id) { o=el; return; } }); return o; };

$(document).ready(function () {

    console.log('ready');

    $('#filterContainer').on('click', '#statusFilterBtnContainer a', function(event){
        event.preventDefault();
        $('#statusFilterBtnContainer a').removeClass('active');
        $('#catFilterBtnContainer a').removeClass('active');
        $('#catFilterBtnContainer #all').addClass('active');
        $(this).addClass('active');

        filterTable();
    });
    $('#filterContainer').on('click', '#catFilterBtnContainer a', function(event){
        event.preventDefault();
        $('#catFilterBtnContainer a').removeClass('active');
        $(this).addClass('active');

        filterTable();
    });

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

    $('#searchInput').on('keyup', function(event) {
        console.log($(this).val());
    });

    /*
    this gets the data for filename from data array
    getObj("85724.jpg", data, 'filename')

    $('#uploadModal').on('hidden.bs.modal', function () {
        $('#uploadModal #file_category').val("");
        $('#uploadModal #file1').val("");
        $('#uploadModal #file_name').val("");
        $('#uploadModal #file_expiry').val("");
        $('#uploadModal #file_url').val("");
        $('#uploadModal #file_comments').val("");
    });

    $('#editModal').on('shown.bs.modal', function (e) {
        var tiggerElement = e.relatedTarget
            fileCategory = tiggerElement.getAttribute('data-category'),
            fileName = tiggerElement.getAttribute('data-file'),
            fileExpiry = tiggerElement.getAttribute('data-expiry'),
            fileUrl = tiggerElement.getAttribute('data-url');
        
        $('#editModal .modal-header h4').html("Edit " + fileName);
        $('#editModal #file_category').val(fileCategory);
        $('#editModal #file_name').val(fileName);
        $('#editModal #file_expiry').val(fileExpiry);
        $('#editModal #file_url').val(fileUrl);
    });
    $('#editModal').on('hidden.bs.modal', function () {
        $('#editModal .modal-header h4').html("");
        $('#editModal #file_category').val("");
        $('#editModal #file_name').val("");
        $('#editModal #file_expiry').val("");
        $('#editModal #file_url').val("");
    });

    $('#deleteModal').on('shown.bs.modal', function (e) {
        var clickedFile = e.relatedTarget,
            clickedFileName = clickedFile.getAttribute('data-file');

        $('#deleteModal .modal-header h4').html("Delete " + clickedFileName);
        $('#deleteModal').modal();
    });

    $('#imageModal').on('shown.bs.modal', function (e) {
        var clickedImage = e.relatedTarget,
            clickedImageUrl = clickedImage.getAttribute('data-url');

        $('#imageModal .modal-content img').attr('src',clickedImageUrl);
        $('#imageModal').modal();
    });
*/
});

/*
//Search for a file
function searchTable() {
    input = document.getElementById("searchInput");
    filter = input.value;
    for (i = 0; i < tr.length; i++) {
        name_td = tr[i].getElementsByTagName("td")[2];
        created_td = tr[i].getElementsByTagName("td")[3];
        expires_td = tr[i].getElementsByTagName("td")[4];
        url_td = tr[i].getElementsByTagName("td")[5];
        submitter_td = tr[i].getElementsByTagName("td")[7];
        if (name_td+created_td+expires_td+url_td+submitter_td) {
            if (name_td.innerHTML.indexOf(filter)+created_td.innerHTML.indexOf(filter)+expires_td.innerHTML.indexOf(filter)+url_td.innerHTML.indexOf(filter)+submitter_td.innerHTML.indexOf(filter) > -5) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        } 
    }
}
*/

//filter table by status and category
function filterTable() {
    const _status = $('#statusFilterBtnContainer a.active').attr('rel'),
          _category = $('#catFilterBtnContainer a.active').attr('rel');

    console.log(`${_status} ${_category}`);
    
    $('#table-info-body tr').hide(); // reset
    if (_status === 'all') {
        if (_category === 'all') {
            $('#table-info-body tr').show();
        } else {
            $(`#table-info-body tr[data-category="${_category}"]`).show();
        }
    } else {
        if (_category === 'all') {
            $(`#table-info-body tr[data-status="${_status}"]`).show();
        } else {
            $(`#table-info-body tr[data-category="${_category}"][data-status="${_status}"]`).show();
        }
    }
    
    // TODO update the counts from data and not from visible 
    const counts = { 
            active_status: _status,
            active: $('#table-info-body tr:visible[data-status="active"]').length, 
            archive: $('#table-info-body tr:visible[data-status="archive"]').length, 
            active_category: _category,
            events: $('#table-info-body tr:visible[data-category="events"]').length, 
            cet: $('#table-info-body tr:visible[data-category="cet"]').length, 
            src: $('#table-info-body tr:visible[data-category="src"]').length
        };

    $('#catFilterBtnContainer').html(tmpl('tmpl-filter-category', counts));
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
