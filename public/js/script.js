const form = document.getElementById("form");
const message = document.getElementById("message");
const result = document.getElementById("result");
const viewUrl = document.getElementById("view-url");
const deleteUrl = document.getElementById("delete-url");
const copyView = document.getElementById("copy-view");
const copyDelete = document.getElementById("copy-delete");
const submit = document.getElementById("submit");

copyView.disabled = true;
copyDelete.disabled = true;

form.addEventListener("submit", async(event) => {
    //フォーム送信をキャンセル
    event.preventDefault();
    //二重送信防止
    submit.disabled = true;

    submit.textContent = "アップロード中...";

    const formData = new FormData(form);
    //PHPからjsonデータを受信
    try{
        const response = await fetch("upload.php", {
          method: "POST",
          body: formData
        });

        if(!response.ok) {
          throw new Error("HTTPエラー");
        }
        const data = await response.json();
        console.log(data);
        
        //jsonデータ受信後HTML更新
        message.textContent = "アップロードが成功しました";
        result.hidden = false;

        copyView.disabled = false;
        copyDelete.disabled = false;

        copyView.textContent = "コピー";
        copyDelete.textContent = "コピー";

        viewUrl.textContent = data.view_url; 
        viewUrl.href = data.view_url;

        deleteUrl.textContent = data.delete_url; 
        deleteUrl.href = data.delete_url;

    } catch (error) {
        console.error(error);

        message.textContent = "アップロードに失敗しました";
        result.hidden = false;

        copyView.disabled = true;
        copyDelete.disabled = true;

        viewUrl.textContent = ""; 
        viewUrl.href = "";

        deleteUrl.textContent = ""; 
        deleteUrl.href = "";
    } finally {
        submit.disabled = false;
        submit.textContent = "Upload Image";
    }
});

//閲覧用URLコピー
copyView.addEventListener("click", async () => {
    try {
      await navigator.clipboard.writeText(viewUrl.textContent);
      copyView.textContent = "コピーしました！";
    } catch (error) {
      console.error(error);
      alert("コピーに失敗しました。");
    }
});

//削除用URLコピー
copyDelete.addEventListener("click", async () => {
    try {
      await navigator.clipboard.writeText(deleteUrl.textContent);
      copyDelete.textContent = "コピーしました！";
    } catch (error) {
      console.error(error);
      alert("コピーに失敗しました。");
    }
});
