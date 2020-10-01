var input, filter, td, i;
var table = document.getElementById("filesTable"),
    tr = table.getElementsByTagName("tr"),
    categoryBtnContainer = document.getElementById("catFilterBtnContainer"),
    statusBtnContainer = document.getElementById("statusFilterBtnContainer"),
    categoryBtns = categoryBtnContainer.getElementsByClassName("btn");
    statusBtns = statusBtnContainer.getElementsByClassName("btn");

var _h = document.body.clientHeight + 52; // Margin and shadows
console.log("resizing: " + _h);
if (window.self !== window.top) {
    window.top.postMessage(JSON.stringify({
        subject: "lti.frameResize",
        height: _h
   }),
"*");
}

for (i = 0; i < categoryBtns.length; i++) {
    categoryBtns[i].addEventListener("click", function(){
        var current = document.getElementsByClassName("active");
        current[0].className = current[0].className.replace(" active", "");
        this.className += " active";
    });
}
  
for (i = 0; i < statusBtns.length; i++) {
    statusBtns[i].addEventListener("click", function(){
        var current = document.getElementsByClassName("active");
        current[0].className = current[0].className.replace(" active", "");
        this.className += " active";
    });
}

$(document).ready(function () {
    getFileCounts();

    $('#uploadModal').on('hidden.bs.modal', function () {
        $('#uploadModal #file_category').val("");
        $('#uploadModal #file1').val("");
        $('#uploadModal #file_name').val("");
        $('#uploadModal #file_expiry').val("");
        $('#uploadModal #file_url').val("");
        $('#uploadModal #file_comments').val("");
    });

    $('#editModal').on('shown.bs.modal', function (e) {
        var tiggerElement = e.relatedTarget,
            fileId = tiggerElement.getAttribute('id'),
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
    input = document.getElementById("myInput");
    filter = input.value;
    for (i = 0; i < tr.length; i++) {
        name_td = tr[i].getElementsByTagName("td")[2];
        date_td = tr[i].getElementsByTagName("td")[3];
        url_td = tr[i].getElementsByTagName("td")[4];
        if (name_td+date_td+url_td) {
            if (name_td.innerHTML.indexOf(filter)+date_td.innerHTML.indexOf(filter)+url_td.innerHTML.indexOf(filter) > -3) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        } 
    }
}

//filter table by status and category
function filterTable(c) {
    if (c == "all") c = "";
    filter = c.toUpperCase();
    for (i = 0; i < tr.length; i++) {
        category_td = tr[i].getElementsByTagName("td")[0];
        status_td = tr[i].getElementsByTagName("td")[5];
        if (category_td+status_td) {
            if (category_td.innerHTML.toUpperCase().indexOf(filter)+status_td.innerHTML.toUpperCase().indexOf(filter) > -2) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }       
    }
}

function getFileCounts() {
    var allCount = 0,
        activeCount = 0, 
        archiveCount = 0, 
        srcCount = 0,
        cetCount = 0,
        eventsCount = 0,
        i;
    
    for (i = 0; i < tr.length; i++) {
        status_td = tr[i].getElementsByTagName("td")[5];
        category_td = tr[i].getElementsByTagName("td")[0];
        if (status_td+category_td) {
            if(status_td.innerHTML === "Active") {
                activeCount++;
            } else if(status_td.innerHTML === "Archive"){
                archiveCount++;
            }  
            if(category_td.innerHTML  === "cet") {
                cetCount++;
            } else if(category_td.innerHTML === "src"){
                srcCount++;
            } else if(category_td.innerHTML === "events"){
                eventsCount++;
            }
            allCount = activeCount + archiveCount;           
        }    
      }
    document.getElementById('activeFilter').innerHTML = activeCount + " Active";
    document.getElementById('archiveFilter').innerHTML = archiveCount + " Archive";
    document.getElementById('allFilter').innerHTML = allCount + " All";
    document.getElementById('cetFilter').innerHTML = cetCount + " cet";
    document.getElementById('srcFilter').innerHTML = srcCount + " src";
    document.getElementById('eventsFilter').innerHTML = eventsCount + " events";
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
            x = rows[i].getElementsByTagName("TD")[n];
            y = rows[i + 1].getElementsByTagName("TD")[n];
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
