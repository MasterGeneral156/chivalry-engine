function CheckPasswords(a) {
    $.ajax({
        type: "POST", url: "js/script/check.php", data: "password=" + escape(a), success: function (a) {
            $("#passwordresult").html(a)
        }
    })
}
function goBack() {
    window.history.back()
}
function CheckUsername(a) {
    $.ajax({
        type: "POST", url: "js/script/checkun.php", data: "username=" + escape(a), success: function (a) {
            $("#usernameresult").html(a)
        }
    })
}
function OutputTeam(a) {
    var b = a.value;
    $.ajax({
        type: "POST", url: "js/script/outputteam.php", data: "team=" + escape(b), success: function (a) {
            $("#teamresult").html(a)
        }
    })
}
function CheckEmail(a) {
    $.ajax({
        type: "POST", url: "js/script/checkem.php", data: "email=" + escape(a), success: function (a) {
            $("#emailresult").html(a)
        }
    })
}
function PasswordMatch() {
    pwt1 = $("#password").val(), pwt2 = $("#cpassword").val(), pwt1 == pwt2 ? (document.getElementById("pwerror").className = "has-success", document.getElementById("cpwerror").className = "has-success") : (document.getElementById("pwerror").className = "has-error", document.getElementById("cpwerror").className = "has-error")
}