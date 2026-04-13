<template>
  <AuthLayout>
    <h2 class="title">Jelszó visszaállítása</h2>

    <div v-if="successMessage" class="success-message">
      {{ successMessage }}
    </div>


    <div v-if="!emailSent" class="div-form">
      <p class="info-text">
        Adja meg az email címét, és küldünk egy visszaállítási linket.
      </p>

      <form @submit.prevent="handleResetPassword" class="auth-form">
        <input
          type="email"
          v-model="form.email"
          placeholder="Email cím *"
          required
          maxlength="255" 
          :disabled="loading"
        />

        <button class="btn_auth" type="submit" :disabled="loading">
          {{ loading ? "Küldés..." : "Link küldése" }}
        </button>

        <p v-if="error" class="error">{{ error }}</p>
      </form>
    </div>

    <!-- Ha már elküldtük -->
    <div v-else class="success-container">
      <div class="success-icon">✓</div>
      <h3 class="success-title">Email elküldve!</h3>
      <p class="success-text">Ellenőrizze az emailjét a további utasításokért.</p>
      <button @click="resetForm" class="btn_auth secondary-btn">
        Új email küldése
      </button>
    </div>

    <p class="register-text">
      <router-link to="/login">Vissza a bejelentkezéshez</router-link>
    </p>
  </AuthLayout>
</template>

<script>
import AuthLayout from "./AuthLayout.vue";
import axios from "axios";

export default {
  components: { AuthLayout },
  data() {
    return {
      form: {
        email: "",
      },
      loading: false,
      error: "",
      successMessage: "",
      emailSent: false,
    };
  },
  methods: {
    async handleResetPassword() {
      if (!this.form.email) {
        this.error = "Kérjük, adja meg az email címét!";
        return;
      }

      this.loading = true;
      this.error = "";

      try {
        const response = await axios.post("/forgot-password", {
          email: this.form.email,
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
          this.successMessage = data.message;
          this.emailSent = true;
        } else {
          this.error = data.message || "Ismeretlen hiba";
        }
      } catch (err) {
        console.error("Hiba:", err);
        this.error = err.response?.data?.message || "Nincs ezzel az email címmel regisztrált felhasználó.";
      } finally {
        this.loading = false;
      }
    },
    resetForm() {
      this.emailSent = false;
      this.successMessage = "";
      this.error = "";
      this.form.email = "";
    },
  },
};
</script>

<style scoped>

.div-form{
  float: right;
}


.auth-form {
  width: 100%;
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
  margin: auto;
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

.secondary-btn {
  background: #8a1212;
  margin-top: 1rem;
}

.secondary-btn:hover:not(:disabled) {
  background: #a51616;
}

.title {
  font-size: 2.5rem;
  color: #9d1f1f;
  margin-bottom: 1.5rem;
}

.info-text {
  font-size: 0.95rem;
  color: #555;
  margin-bottom: 1.5rem;
  text-align: center;
}



.error {
  color: #c70000;
  text-align: center;
  margin-top: 0.5rem;
  font-size: 0.9rem;
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

/* Sikeres állapot stílusok */
.success-container {
  text-align: center;
  padding: 1rem 0;
}

.success-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.success-title {
  font-size: 1.5rem;
  color: #333;
  margin-bottom: 0.5rem;
}

.success-text {
  color: #555;
  margin-bottom: 0.5rem;
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

  .info-text {
    font-size: 0.85rem;
  }

  .register-text {
    font-size: 0.9rem;
  }
}
</style>