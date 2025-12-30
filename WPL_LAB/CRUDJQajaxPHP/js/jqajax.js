
    $("#btnadd").click(function (e) {
        e.preventDefault();
        console.log("Save Button clicked")
        
        let nm = $("#nameid").val()
        let em = $("#emailid").val()
        let pw = $("#passwordid").val()
        // console.log(nm);
        // console.log(em);
        // console.log(pw);

        mydata = {name: nm , email: em , password: pw};
        // console.log(mydata);

        $.ajax({
            url:"insert.php",
            method:"POST",
            data: JSON.stringify(mydata),
            success: function(data){
                console.log(data);
            }

        })
    
    });

