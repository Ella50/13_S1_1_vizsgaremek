<template>
  <AuthLayout>
    <h2 class="title">Új jelszó beállítása</h2>

    <div v-if="successMessage" class="success-message">
      {{ successMessage }}
    </div>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>

    <form @submit.prevent="handleResetPassword" class="auth-form">
      <PasswordInput 
        v-model="form.password"
        placeholder="Új jelszó *"
        :required="true"
        :minlength="8"
      />
      
      <PasswordInput 
        v-model="form.password_confirmation"
        placeholder="Jelszó megerősítése *"
        :required="true"
        :minlength="8"
      />

      <button class="btn_auth" type="submit" :disabled="loading">
        {{ loading ? "Feldolgozás..." : "Jelszó megváltoztatása" }}
      </button>
    </form>

    <p class="register-text">
      <router-link to="/login">Vissza a bejelentkezéshez</router-link>
    </p>
  </AuthLayout>
</template>


<script>
import AuthLayout from "./AuthLayout.vue";
import PasswordInput from "./PasswordInput.vue";
import axios from "axios";

export default {
  components: { AuthLayout, PasswordInput },
  props: {
    token: {
      type: String,
      required: true,
    },
    email: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      form: {
        token: this.token,
        email: decodeURIComponent(this.email || ""),
        password: "",
        password_confirmation: "",
      },
      loading: false,
      error: "",
      successMessage: "",
    };
  },
  methods: {
    async handleResetPassword() {
      if (!this.form.password || !this.form.password_confirmation) {
        this.error = "Minden mező kitöltése kötelező!";
        return;
      }

      if (this.form.password !== this.form.password_confirmation) {
        this.error = "A jelszavak nem egyeznek!";
        return;
      }

      if (this.form.password.length < 8) {
        this.error = "A jelszónak legalább 8 karakter hosszúnak kell lennie!";
        return;
      }

      this.loading = true;
      this.error = "";

      try {
        const response = await axios.post("/reset-password", {
          token: this.form.token,
          email: this.form.email,
          password: this.form.password,
          password_confirmation: this.form.password_confirmation,
        });

        // BOM kezelés
        let data = response.data;
        if (typeof data === "string") {
          if (data.charCodeAt(0) === 0xfeff || data.charCodeAt(0) === 65279) {
            data = data.slice(1);
            data = JSON.parse(data);
          }
        }

        if (data.success) {
          this.successMessage = data.message || "Jelszó sikeresen megváltoztatva!";
          // 3 másodperc múlva átirányítás
          setTimeout(() => {
            this.$router.push("/login");
          }, 3000);
        } else {
          this.error = data.message || "Hiba történt";
        }
      } catch (err) {
        console.error("Reset password error:", err);

        // Validációs hibák kezelése
        if (err.response?.status === 422) {
          const errors = err.response.data.errors;
          if (errors) {
            const errorMessages = [];
            for (let field in errors) {
              errorMessages.push(errors[field][0]);
            }
            this.error = errorMessages.join(", ");
          } else {
            this.error = err.response.data.message || "Validációs hiba";
          }
        } else {
          this.error = err.response?.data?.message || "Hálózati hiba";
        }
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.auth-form {
  width: 70%;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.auth-form input {
  width: 100%;
  padding: 0.8rem;
  border: 2px solid #555;
  border-radius: 25px;
  font-size: 1rem;
}

.btn_auth {
  width: 100%;
  padding: 0.9rem;
  background: #1fa317;
  color: #fff;
  font-size: 1.2rem;
  font-weight: bold;
  border: none;
  border-radius: 25px;
  cursor: pointer;
  transition: background 0.2s;
}

.btn_auth:disabled {
  background: #8f8f8f;
  cursor: not-allowed;
}

.title {
  font-size: 2.5rem;
  color: #9d1f1f;
  margin-bottom: 1.5rem;
}

.register-text {
  margin-top: 1.5rem;
  font-size: 1rem;
  color: #8a1212;
  text-align: center;
}

.register-text a {
  color: #8a1212;
  text-decoration: underline;
}

.error-message {
  background: #ffebee;
  color: #c62828;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  border: 1px solid #ffcdd2;
  text-align: center;
}

.success-message {
  background: #e8f5e9;
  color: #2e7d32;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  text-align: center;
  border: 1px solid #c8e6c9;
}


@media (max-width: 768px) {
  .auth-form {
    width: 100%;
    max-width: 400px;
    margin: 0 auto;
  }

  .title {
    font-size: 1.8rem;
    margin-bottom: 1.2rem;
  }
}

@media (max-width: 480px) {
  .auth-form input,
  .btn_auth {
    padding: 0.7rem 1rem;
    font-size: 0.95rem;
  }

  .title {
    font-size: 1.5rem;
    margin-bottom: 1rem;
  }

  .register-text {
    font-size: 0.9rem;
  }
}
</style>