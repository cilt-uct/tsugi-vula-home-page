var input, filter, td, i;
var table = document.getElementById("filesTable"),
    tr = document.getElementsByTagName("tr");

$(document).ready(function () {
    var c = "Active",
        clickedStatus = "active"
        clickedCategory = "";

    filterTable(c);

    $('.statusFilter').click(function () {
        $(".statusFilter").removeClass("active");
        $(".categoryFilter").removeClass("active");
        $(this).addClass(" active"); 
        clickedStatus = $(this).attr("id");
        filterTable(clickedStatus);
    });

    $('.categoryFilter').click(function () {
        $(".categoryFilter").removeClass("active");
        $(this).addClass(" active"); 
        clickedCategory = $(this).attr("id");
        filterByCategory(clickedStatus, clickedCategory);
    });

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
});


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

//filter table by status and category
function filterTable(c) {
    if (c == "all") { c = "";  } 

    var statusFilter = c.toUpperCase(),
        srcCount = 0, cetCount = 0, eventsCount = 0,
        allCount = 0, activeCount = 0, archiveCount = 0;
   
    for (i = 0; i < tr.length; i++) {
        var category_td = tr[i].getElementsByTagName("td")[0],
            status_td = tr[i].getElementsByTagName("td")[6];

        if (status_td+category_td) {

            //count per status
            if(status_td.innerHTML === "Active") {
                activeCount++;
            } else if(status_td.innerHTML === "Archive"){
                archiveCount++;
            }
            allCount = activeCount + archiveCount; 

            //count per category and filter by status
            if (status_td.innerHTML.toUpperCase().indexOf(statusFilter) > -1) {
                if(category_td.innerHTML  === "cet") {
                    cetCount++;
                } else if(category_td.innerHTML === "src"){
                    srcCount++;
                } else if(category_td.innerHTML === "events"){
                    eventsCount++;
                }
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }       
    }
    document.getElementById('active').innerHTML = "Active <span class='badge badge-light'>"+ activeCount + "</span>";
    document.getElementById('archive').innerHTML = "Archive <span class='badge badge-light'>" + archiveCount + "</span>";
    document.getElementById('all').innerHTML = "All <span class='badge badge-light'>" + allCount + "</span>";
    document.getElementById('src').innerHTML = "src  <span class='badge badge-light'>" + srcCount +"</span>" ;
    document.getElementById('events').innerHTML = "events  <span class='badge badge-light'>" + eventsCount +"</span>";
    document.getElementById('cet').innerHTML = "cet  <span class='badge badge-light'>" + cetCount +"</span>";
}

function filterByCategory(clickedStatus, clickedCategory) {
    if (clickedStatus == "all") { clickedStatus = "";  } 
    
    var statusFilter = clickedStatus.toUpperCase(),
        categoryFilter = clickedCategory.toUpperCase();

    for (i = 0; i < tr.length; i++) {
        var category_td = tr[i].getElementsByTagName("td")[0],
            status_td = tr[i].getElementsByTagName("td")[6];

        if (status_td+category_td) {
            if (status_td.innerHTML.toUpperCase().indexOf(statusFilter) > -1 && category_td.innerHTML.toUpperCase().indexOf(categoryFilter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}

// sort the table by table headers
function sortTable(n) {
    var rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
    switching = true;
    dir = "asc"; 
    while (switching) {
        switching = false;
        rows = table.rows;
        for (i = 1; i < (rows.length - 1); i++) {
            shouldSwitch = false;
            x = rows[i].getElementsByTagName("td")[n];
            y = rows[i + 1].getElementsByTagName("td")[n];
            if (dir == "asc") {
                if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                    shouldSwitch= true;
                    break;
                }
            } else if (dir == "desc") {
                if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            }
        }
        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            switchcount ++;      
        } else {
            if (switchcount == 0 && dir == "asc") {
                dir = "desc";
                switching = true;
            }
        }
    }
}
