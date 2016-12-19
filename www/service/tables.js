function colorize(){
	$('table.col tr:odd').addClass('odd');
	$('table.col tr:even').addClass('even');
}

function serializeTable(obj, caption){
    var table = '';
    var head = '';
    var row = '';
    var cell = '';
    table = '<table class="col" border="1" rules="all" cellpadding="5"><caption>'+ caption +'</caption>';
    table += "<tr>";

    for (head in obj[0]) {
        table += "<th>"+head+"</th>";
    }
    table += "</tr>";

    for (row in obj) {
        table += "<tr>";
        for (cell in obj[row]) {
            table += "<td>"+obj[row][cell]+"</td>";
        }
        table += "</tr>";
    }
    table += "</table>";
    return table;
}