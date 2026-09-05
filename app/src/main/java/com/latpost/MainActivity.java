package com.latpost;

import android.app.Activity;
import android.content.Context;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.graphics.drawable.GradientDrawable;
import android.os.Bundle;
import android.text.InputType;
import android.view.Gravity;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ImageButton;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ScrollView;
import android.widget.TextView;
import android.widget.Toast;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.Stack;

public class MainActivity extends Activity {
    private static final String API = "https://v1-a1.latpost.com/";
    private static final String PREFS = "latpost_session";
    private static final int BRAND_BLUE = Color.rgb(20, 78, 160);
    private static final int TEXT_DARK = Color.rgb(24, 37, 56);
    private static final int TEXT_MUTED = Color.rgb(96, 109, 126);
    private final ExecutorService executor = Executors.newSingleThreadExecutor();
    private SharedPreferences prefs;
    private LinearLayout content;
    private TextView title;
    private ScrollView feedScroll;
    private int feedPage = 1;
    private boolean feedLoading;
    private boolean feedHasMore = true;
    private final Stack<String> screenHistory = new Stack<>();
    private String currentScreen = "start";

    @Override
    protected void onCreate(Bundle state) {
        super.onCreate(state);
        prefs = getSharedPreferences(PREFS, MODE_PRIVATE);
        showStart();
    }

    private void base(String screenTitle) {
        if (!screenTitle.equals(currentScreen)) {
            screenHistory.push(currentScreen);
            currentScreen = screenTitle;
        }
        LinearLayout root = new LinearLayout(this);
        root.setOrientation(LinearLayout.VERTICAL);
        root.setBackgroundColor(Color.WHITE);

        LinearLayout bar = new LinearLayout(this);
        bar.setGravity(Gravity.CENTER_VERTICAL);
        bar.setPadding(dp(20), dp(14), dp(20), dp(14));
        bar.setBackgroundColor(BRAND_BLUE);
        title = text("Latpost", 22, Color.WHITE);
        bar.addView(title, new LinearLayout.LayoutParams(0, -2, 1));
        root.addView(bar);

        ScrollView scroll = new ScrollView(this);
        feedScroll = scroll;
        content = new LinearLayout(this);
        content.setOrientation(LinearLayout.VERTICAL);
        content.setPadding(dp(18), dp(20), dp(18), dp(30));
        scroll.addView(content);
        root.addView(scroll, new LinearLayout.LayoutParams(-1, 0, 1));
        if (prefs.getString("token", null) != null) {
            root.addView(footer(), new LinearLayout.LayoutParams(-1, -2));
        }
        setContentView(root);
    }

    private LinearLayout footer() {
        LinearLayout footer = new LinearLayout(this);
        footer.setGravity(Gravity.CENTER);
        footer.setBackgroundColor(Color.WHITE);
        footer.setPadding(dp(6), dp(3), dp(6), dp(3));

        ImageButton home = footerButton(com.latpost.R.drawable.ic_home, "Ana sayfa");
        home.setOnClickListener(v -> {
            if (prefs.getString("token", null) == null) showStart(); else showFeed();
        });
        footer.addView(home, new LinearLayout.LayoutParams(0, -2, 1));

        ImageButton account = footerButton(com.latpost.R.drawable.ic_account, "Hesap");
        account.setOnClickListener(v -> {
            if (prefs.getString("token", null) == null) showLogin(); else showProfile();
        });
        footer.addView(account, new LinearLayout.LayoutParams(0, -2, 1));

        ImageButton info = footerButton(com.latpost.R.drawable.ic_info, "Bilgi");
        info.setOnClickListener(v -> showLegal("Bilgi", privacyText() + "\n\n" + termsText()));
        footer.addView(info, new LinearLayout.LayoutParams(0, -2, 1));
        return footer;
    }

    private ImageButton footerButton(int icon, String description) {
        ImageButton button = new ImageButton(this);
        button.setImageResource(icon);
        button.setContentDescription(description);
        button.setBackgroundColor(Color.TRANSPARENT);
        button.setPadding(dp(16), dp(10), dp(16), dp(10));
        return button;
    }

    private void showStart() {
        base("Latpost");
        String token = prefs.getString("token", null);
        if (token != null) {
            showFeed();
            return;
        }
        content.addView(text("Haberleri sade ve hizli okuyun.", 25, TEXT_DARK));
        content.addView(space(14));
        Button login = button("Giris yap");
        login.setOnClickListener(v -> showLogin());
        content.addView(login);
        Button register = button("Kayit ol");
        register.setOnClickListener(v -> showRegister());
        content.addView(register);
        Button privacy = button("Gizlilik politikasi");
        privacy.setOnClickListener(v -> showLegal("Gizlilik politikasi", privacyText()));
        content.addView(privacy);
        Button terms = button("Kullanici sozlesmesi");
        terms.setOnClickListener(v -> showLegal("Kullanici sozlesmesi", termsText()));
        content.addView(terms);
        Button data = button("Veri silme ve KVKK");
        data.setOnClickListener(v -> showLegal("Veri silme ve KVKK", dataText()));
        content.addView(data);
    }

    private void showLegal(String screenTitle, String body) {
        base(screenTitle);
        content.addView(text(body, 16, Color.rgb(35, 45, 40)));
        Button back = button("Geri don");
        back.setOnClickListener(v -> showStart());
        content.addView(back);
    }

    private String privacyText() {
        return "Son guncelleme: 5 Eylul 2026\n\n"
            + "Latpost, hesap olusturmak ve haberleri gostermek icin ad soyad ve e-posta adresi toplar."
            + " Sifreler hashlenerek saklanir. Hesap aktivasyonu icin e-posta ile tek kullanimlik OTP gonderilir."
            + " Token, API isteklerini yetkilendirmek icin uygulamanin ozel alaninda saklanir."
            + " Veriler reklam profili olusturmak veya satmak icin kullanilmaz."
            + " Hesabinizi uygulamadaki Profil > Hesabi sil seceneginden silebilirsiniz."
            + " Silme istegi kullanici kaydini pasife alir ve oturum tokenlarini siler."
            + " Destek: support@latpost.com";
    }

    private String termsText() {
        return "Latpost Kullanici Sozlesmesi\n\n"
            + "Latpost, kullanicilarin haber ve gonderi iceriklerini okuyabildigi bir platformdur."
            + " Kullanici, hesabinda paylastigi icerikten ve hesabinin guvenliginden sorumludur."
            + " Hukuka aykiri, tehdit edici, nefret soylemi iceren veya baskalarinin haklarini ihlal eden icerikler yasaktir."
            + " Latpost, kurallari ihlal eden hesaplari kapatma hakkini sakli tutar."
            + " Hizmeti kullanarak bu kosullari kabul etmis olursunuz."
            + " Destek: support@latpost.com";
    }

    private String dataText() {
        return "Veri silme ve KVKK bilgilendirmesi\n\n"
            + "Hesabinizi silmek icin Profil ekranindaki Hesabi sil butonunu kullanin."
            + " Bu islem UserActive durumunu pasife alir, aktif tokenlari siler ve hesaba erisimi kapatir."
            + " Yasal saklama zorunlulugu bulunan kayitlar ilgili sure boyunca tutulabilir."
            + " Kisisel verilerinizle ilgili talepler icin support@latpost.com adresine yazabilirsiniz.";
    }

    private void showLogin() {
        base("Giris");
        EditText mail = input("E-posta", false);
        EditText password = input("Sifre", true);
        content.addView(mail);
        content.addView(password);
        Button submit = button("Giris yap");
        submit.setOnClickListener(v -> {
            submit.setEnabled(false);
            request("UserLogin.php", response -> {
                submit.setEnabled(true);
                if (isActivation(response)) {
                    saveToken(response);
                    showActivation();
                } else if (isSuccess(response)) {
                    saveToken(response);
                    showFeed();
                } else error(response);
            }, "Mail", value(mail), "Password", value(password));
        });
        content.addView(submit);
        Button register = button("Yeni hesap olustur");
        register.setOnClickListener(v -> showRegister());
        content.addView(register);
    }

    private void showRegister() {
        base("Kayit ol");
        EditText name = input("Ad soyad", false);
        EditText mail = input("E-posta", false);
        EditText password = input("Sifre", true);
        content.addView(name);
        content.addView(mail);
        content.addView(password);
        Button submit = button("Kayit ol");
        submit.setOnClickListener(v -> {
            submit.setEnabled(false);
            request("UserRegister.php", response -> {
                submit.setEnabled(true);
                if (isActivation(response)) {
                    saveToken(response);
                    showActivation();
                } else if (isSuccess(response)) {
                    saveToken(response);
                    showFeed();
                } else error(response);
            }, "NameSurname", value(name), "Mail", value(mail), "Password", value(password));
        });
        content.addView(submit);
        Button back = button("Giris ekranina don");
        back.setOnClickListener(v -> showLogin());
        content.addView(back);
    }

    private void showActivation() {
        base("Hesabi aktif et");
        content.addView(text("E-posta adresinize gelen 6 haneli kodu girin.", 17, Color.DKGRAY));
        EditText otp = input("OTP kodu", false);
        otp.setInputType(InputType.TYPE_CLASS_NUMBER);
        content.addView(otp);
        Button activate = button("Hesabi aktif et");
        activate.setOnClickListener(v -> {
            activate.setEnabled(false);
            get("UserActive.php?otp=" + encode(value(otp)), response -> {
                activate.setEnabled(true);
                if (isSuccess(response)) showFeed(); else error(response);
            });
        });
        content.addView(activate);
        Button logout = button("Bu hesabi kullanma");
        logout.setOnClickListener(v -> clearSession());
        content.addView(logout);
    }

    private void showFeed() {
        base("Latpost");
        content.addView(text("Guncel haberler", 26, TEXT_DARK));
        feedPage = 1;
        feedHasMore = true;
        feedLoading = false;
        loadFeedPage(true);
        feedScroll.setOnScrollChangeListener((v, scrollX, scrollY, oldScrollX, oldScrollY) -> {
            View child = feedScroll.getChildAt(0);
            if (child != null && child.getBottom() - (scrollY + feedScroll.getHeight()) < dp(500)) {
                loadFeedPage(false);
            }
        });
    }

    private void loadFeedPage(boolean firstPage) {
        if (feedLoading || !feedHasMore) return;
        feedLoading = true;
        int requestedPage = feedPage;
        TextView loading = text(firstPage ? "Haberler yukleniyor..." : "Daha fazla haber yukleniyor...", 15, Color.GRAY);
        content.addView(loading);
        getAuth("FulPost.php?page=" + requestedPage, response -> {
            feedLoading = false;
            content.removeView(loading);
            if (isActivation(response)) {
                showActivation();
                return;
            }
            if (!isSuccess(response)) {
                error(response);
                return;
            }
            try {
                JSONArray posts = new JSONObject(response).optJSONArray("data");
                if (posts == null || posts.length() == 0) {
                    feedHasMore = false;
                    if (firstPage) content.addView(text("Henuz haber yok.", 17, Color.GRAY));
                    return;
                }
                for (int i = 0; i < posts.length(); i++) addPostCard(posts.getJSONObject(i));
                feedPage++;
            } catch (Exception e) {
                toast("Haberler okunamadi.");
            }
        });
    }

    private void addPostCard(JSONObject post) {
        LinearLayout card = new LinearLayout(this);
        card.setOrientation(LinearLayout.VERTICAL);
        card.setPadding(dp(16), dp(16), dp(16), dp(18));
        card.setBackgroundColor(Color.WHITE);
        card.setClickable(true);
        card.setFocusable(true);
        card.setForeground(getDrawable(android.R.drawable.list_selector_background));
        card.setBackground(cardBackground());
        card.setOnClickListener(v -> showPost(post.optInt("PostId", 0)));
        LinearLayout authorRow = new LinearLayout(this);
        authorRow.setGravity(Gravity.CENTER_VERTICAL);
        ImageView avatar = avatar(post.optString("UserAvatar", ""));
        authorRow.addView(avatar);
        TextView author = text(post.optString("NameSurname", "Latpost"), 14, BRAND_BLUE);
        authorRow.addView(author);
        card.addView(authorRow);
        TextView body = text(post.optString("PostContent", ""), 18, TEXT_DARK);
        body.setPadding(0, dp(7), 0, dp(8));
        card.addView(body);
        addPictures(card, post.optJSONArray("Pictures"));
        LinearLayout.LayoutParams cardParams = new LinearLayout.LayoutParams(-1, -2);
        cardParams.setMargins(0, 0, 0, dp(14));
        content.addView(card, cardParams);
    }

    private void showPost(int id) {
        base("Haber");
        TextView loading = text("Haber yukleniyor...", 16, Color.GRAY);
        content.addView(loading);
        getAuth("GetPost.php?id=" + id, response -> {
            content.removeView(loading);
            if (isActivation(response)) { showActivation(); return; }
            if (!isSuccess(response)) { error(response); return; }
            try {
                JSONObject post = new JSONObject(response).getJSONObject("data");
                LinearLayout authorRow = new LinearLayout(this);
                authorRow.setGravity(Gravity.CENTER_VERTICAL);
                authorRow.addView(avatar(post.optString("UserAvatar", "")));
                authorRow.addView(text(post.optString("NameSurname", "Latpost"), 15, BRAND_BLUE));
                content.addView(authorRow);
                content.addView(text(post.optString("PostContent", ""), 23, TEXT_DARK));
                addPictures(content, post.optJSONArray("Pictures"));
                content.addView(space(14));
                Button back = button("Tum haberlere don");
                back.setOnClickListener(v -> showFeed());
                content.addView(back);
            } catch (Exception e) { toast("Haber okunamadi."); }
        });
    }

    private ImageView avatar(String url) {
        ImageView image = new ImageView(this);
        image.setLayoutParams(new LinearLayout.LayoutParams(dp(44), dp(44)));
        image.setScaleType(ImageView.ScaleType.CENTER_CROP);
        image.setImageResource(com.latpost.R.drawable.ic_account);
        if (url != null && !url.isEmpty()) loadImage(url, image, true);
        return image;
    }

    private void addPictures(LinearLayout parent, JSONArray pictures) {
        if (pictures == null) return;
        for (int i = 0; i < pictures.length(); i++) {
            String url = pictures.optString(i, "");
            if (url.isEmpty()) continue;
            ImageView image = new ImageView(this);
            image.setAdjustViewBounds(true);
            image.setMinimumHeight(dp(120));
            image.setScaleType(ImageView.ScaleType.CENTER_CROP);
            image.setImageResource(com.latpost.R.drawable.ic_image_placeholder);
            parent.addView(image, new LinearLayout.LayoutParams(-1, -2));
            loadImage(url, image, false);
        }
    }

    private void loadImage(String url, ImageView target, boolean small) {
        executor.execute(() -> {
            try {
                HttpURLConnection connection = (HttpURLConnection) new URL(url).openConnection();
                connection.setConnectTimeout(8000);
                connection.setReadTimeout(12000);
                Bitmap bitmap = BitmapFactory.decodeStream(connection.getInputStream());
                connection.disconnect();
                if (bitmap != null) runOnUiThread(() -> target.setImageBitmap(bitmap));
            } catch (Exception ignored) {
            }
        });
    }

    private GradientDrawable cardBackground() {
        GradientDrawable background = new GradientDrawable();
        background.setColor(Color.WHITE);
        background.setStroke(dp(1), Color.rgb(231, 235, 241));
        background.setCornerRadius(dp(14));
        return background;
    }

    private void showProfile() {
        base("Profil");
        TextView loading = text("Profil yukleniyor...", 16, Color.GRAY);
        content.addView(loading);
        getAuth("UserProfile.php", response -> {
            content.removeView(loading);
            if (isActivation(response)) { showActivation(); return; }
            if (!isSuccess(response)) { error(response); return; }
            try {
                JSONObject user = new JSONObject(response).getJSONObject("data");
                content.addView(text(user.optString("NameSurname"), 23, Color.rgb(25, 35, 30)));
                content.addView(text(user.optString("Mail"), 16, Color.GRAY));
            } catch (Exception e) { toast("Profil okunamadi."); }
            Button delete = button("Hesabi sil");
            delete.setOnClickListener(v -> deleteAccount());
            content.addView(delete);
            Button logout = button("Cikis yap");
            logout.setOnClickListener(v -> clearSession());
            content.addView(logout);
            Button back = button("Haberlere don");
            back.setOnClickListener(v -> showFeed());
            content.addView(back);
        });
    }

    private void deleteAccount() {
        requestAuth("UserUpdate.php", "Action", "delete", response -> {
            if (isSuccess(response)) clearSession(); else error(response);
        });
    }

    private void request(String endpoint, Callback callback, String... fields) {
        executor.execute(() -> {
            String result = post(endpoint, fields, null);
            runOnUiThread(() -> { if (callback != null) callback.done(result); });
        });
    }

    private void requestAuth(String endpoint, String key, String value, Callback callback) {
        executor.execute(() -> {
            String result = post(endpoint, new String[]{key, value}, prefs.getString("token", ""));
            runOnUiThread(() -> callback.done(result));
        });
    }

    private void get(String endpoint, Callback callback) {
        executor.execute(() -> {
            String result = http("GET", endpoint, null, null);
            runOnUiThread(() -> callback.done(result));
        });
    }

    private void getAuth(String endpoint, Callback callback) {
        executor.execute(() -> {
            String result = http("GET", endpoint, null, prefs.getString("token", ""));
            runOnUiThread(() -> callback.done(result));
        });
    }

    private String post(String endpoint, String[] fields, String token) {
        StringBuilder data = new StringBuilder();
        for (int i = 0; i < fields.length; i += 2) {
            if (i > 0) data.append('&');
            data.append(encode(fields[i])).append('=').append(encode(fields[i + 1]));
        }
        return http("POST", endpoint, data.toString(), token);
    }

    private String http(String method, String endpoint, String data, String token) {
        HttpURLConnection connection = null;
        try {
            connection = (HttpURLConnection) new URL(API + endpoint).openConnection();
            connection.setRequestMethod(method);
            connection.setConnectTimeout(10000);
            connection.setReadTimeout(15000);
            connection.setRequestProperty("Accept", "application/json");
            if (token != null && !token.isEmpty()) connection.setRequestProperty("Authorization", "Bearer " + token);
            if ("POST".equals(method)) {
                connection.setDoOutput(true);
                connection.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
                OutputStream out = connection.getOutputStream();
                out.write(data.getBytes("UTF-8"));
                out.close();
            }
            InputStream stream = connection.getResponseCode() >= 400 ? connection.getErrorStream() : connection.getInputStream();
            if (stream == null) return "{\"status\":\"error\",\"message\":\"Sunucu cevabi yok.\"}";
            BufferedReader reader = new BufferedReader(new InputStreamReader(stream, "UTF-8"));
            StringBuilder response = new StringBuilder();
            String line;
            while ((line = reader.readLine()) != null) response.append(line);
            reader.close();
            return response.toString();
        } catch (Exception e) {
            return "{\"status\":\"error\",\"message\":\"Baglanti hatasi.\"}";
        } finally {
            if (connection != null) connection.disconnect();
        }
    }

    private boolean isSuccess(String response) { return status(response).equals("success"); }
    private boolean isActivation(String response) { return status(response).equals("activation_required"); }
    private String status(String response) { try { return new JSONObject(response).optString("status"); } catch (Exception e) { return "error"; } }
    private void saveToken(String response) { try { prefs.edit().putString("token", new JSONObject(response).getString("token")).apply(); } catch (Exception ignored) {} }
    private void error(String response) { try { toast(new JSONObject(response).optString("message", "Bir hata olustu.")); } catch (Exception e) { toast("Bir hata olustu."); } }
    private void clearSession() { prefs.edit().clear().apply(); showStart(); }
    private String value(EditText input) { return input.getText().toString().trim(); }
    private String encode(String value) { try { return URLEncoder.encode(value, "UTF-8"); } catch (Exception e) { return ""; } }
    private int dp(int value) { return (int) (value * getResources().getDisplayMetrics().density + 0.5f); }
    private TextView text(String value, int size, int color) { TextView view = new TextView(this); view.setText(value); view.setTextSize(size); view.setTextColor(color); view.setPadding(0, dp(5), 0, dp(5)); return view; }
    private EditText input(String hint, boolean password) { EditText input = new EditText(this); input.setHint(hint); input.setSingleLine(true); if (password) input.setInputType(InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_VARIATION_PASSWORD); input.setLayoutParams(new LinearLayout.LayoutParams(-1, -2)); return input; }
    private Button button(String value) { Button button = new Button(this); button.setText(value); button.setAllCaps(false); button.setTextColor(BRAND_BLUE); return button; }
    private View space(int size) { View view = new View(this); view.setLayoutParams(new LinearLayout.LayoutParams(1, dp(size))); return view; }
    private void toast(String message) { Toast.makeText(this, message, Toast.LENGTH_LONG).show(); }
    private interface Callback { void done(String response); }

    @Override
    public void onBackPressed() {
        if (!screenHistory.empty()) {
            String previous = screenHistory.pop();
            currentScreen = previous;
            if (previous.equals("Latpost")) showFeed();
            else if (previous.equals("Profil")) showProfile();
            else if (previous.equals("Giris")) showLogin();
            else if (previous.equals("Kayit ol")) showRegister();
            else showStart();
        } else {
            super.onBackPressed();
        }
    }

    @Override protected void onDestroy() { executor.shutdownNow(); super.onDestroy(); }
}
