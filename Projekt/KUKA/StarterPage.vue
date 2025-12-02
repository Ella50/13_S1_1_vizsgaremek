<template>

  <div class="starter-page">
    <div class="login-container">
      <h1>Bejelentkezés</h1>
      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label>Email:</label>
          <input v-model="loginForm.email" type="email" placeholder="pelda@iskola.hu" required>
        </div>
        <div class="form-group">
          <label>Jelszó:</label>
          <input v-model="loginForm.password" type="password" placeholder="diak123" required>
        </div>
        <button type="submit" class="login-btn">Bejelentkezés</button>
      </form>
      
      <div v-if="error" class="error">
        {{ error }}
      </div>
      
      <div class="demo-info">
        <p><strong>Demo bejelentkezés:</strong></p>
        <p>Email: bence.kovacs@iskola.hu</p>
        <p>Jelszó: diak123</p>
      </div>
    </div>
  </div>




 <!--<div id="login">
    <div class="login-wrapped">

      <div class="left-side">
        <img src="/public/eMenza.png" alt="eMenza Logo" class="logo" />
        <img src="/public/falevel.png" class="leaf leaf-top-left" />
        <img src="/public/faag.png" class="leaf leaf-bottom-left" />
        <img src="/public/abrosz.png" class="picnic" />
      </div>


      <div class="right-side">
        <div class="login-box">
          <h1>Bejelentkezés</h1>

          <form @submit.prevent="login">
            <div>
              <label>Email:</label>
              <input type="email" v-model="email" required />
            </div>

            <div>
              <label>Jelszó:</label>
              <input type="password" v-model="password" required />
            </div>

            <button type="submit">Belépés</button>

            <p v-if="error" style="color:red">{{ error }}</p>
          </form>
          <a class="forgot">Elfelejtett jelszó</a>


          <p class="register">
            Még nincs fiókja?
            <a>Regisztráljon most</a>
          </p>
        </div>
      </div>
    </div>
  </div>
  -->
</template>

<script >

import { userService } from '@/services/userService.js'

export default {
  name: 'StarterPage',
  inject: ['emitter'], // ✅ INJECT használata
  data() {
    return {
      loginForm: {
        email: '',
        password: ''
      },
      error: ''
    }
  },
  methods: {
    async handleLogin() {
      try {
        // Mock bejelentkezés
        const users = await userService.getAll()
        const user = users.data.find(u => 
          u.email === this.loginForm.email && 
          this.loginForm.password === 'diak123'
        )
        
        if (user) {
          // Token mentése
          localStorage.setItem('authToken', 'mock-token')
          localStorage.setItem('userData', JSON.stringify(user))
          
          // Bejelentkezési esemény
          this.emitter.emit('login') // ✅ INJECT-elt emitter
          
          // Átirányítás
          this.$router.push('/home')
        } else {
          this.error = 'Hibás email vagy jelszó! Használd a demo adatokat.'
        }
      } catch (err) {
        this.error = 'Hiba történt a bejelentkezés során'
      }
    }
  }
}

  /*import { useRouter } from 'vue-router'
  import axios from 'axios'
  const router = useRouter()

  axios.post("http://localhost:8000/api/login", {
    email: this.email,
    password: this.password
  })
  .then(res => {
    localStorage.setItem("token", res.data.token);
  })
  .catch(err => {
    console.error(err);
  });

  axios.post("http://localhost:8000/api/login", {
    email: this.email,
    password: this.password
  })
  .then(res => {
    localStorage.setItem("token", res.data.token)   // <-- ITT TÁROLÓD
  })



  function login() {
    // itt később majd bekötöd a backend validációt
    router.push('/app')
  }*/

  /*
import axios from "axios";

export default {
  data() {
    return {
      email: "",
      password: "",
      error: ""
    };
  },

  methods: {
    async login() {
      try {
        const res = await axios.post("http://localhost:8000/api/login", {
          email: this.email,
          password: this.password
        },{
          headers: {
            'Content-Type': 'application/json',
            //'Accept': 'application/json'
          }
        });

        // Token tárolása
        localStorage.setItem("token", res.data.token);

        // Sikeres bejelentkezés után pl. átirányítás
        this.$router.push("/app");

      } catch (err) {
        this.error = "Hibás email vagy jelszó!";
      }
    }
  }
};*/
</script>

<style scoped>

.starter-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-container {
  background: white;
  padding: 2rem;
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.2);
  width: 90%;
  max-width: 400px;
}

h1 {
  text-align: center;
  margin-bottom: 1.5rem;
  color: #333;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #555;
}

.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 5px;
  font-size: 1rem;
}

.login-btn {
  width: 100%;
  background: #667eea;
  color: white;
  border: none;
  padding: 0.75rem;
  border-radius: 5px;
  font-size: 1rem;
  cursor: pointer;
  margin-top: 1rem;
}

.login-btn:hover {
  background: #5a6fd8;
}

.error {
  background: #fee;
  color: #c33;
  padding: 0.75rem;
  border-radius: 5px;
  margin-top: 1rem;
  text-align: center;
}

.demo-info {
  margin-top: 1.5rem;
  padding: 1rem;
  background: #f0f8ff;
  border-radius: 5px;
  font-size: 0.9rem;
}

/* 
.login-wrapped{
  display: flex;
  height: 100vh;
  width: 100%;
  overflow: hidden;
}

.left-side {
  flex: 1;
  background: #fff5e0;
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
}

.right-side {
  flex: 1;
  background: #ffcb8d;
  display: flex;
  justify-content: center;
  align-items: center;
}

.login-box {
  text-align: center;
  width: 320px;
}


.logo {
  width: 300px;
}

.leaf {
  position: absolute;
  width: 150px;
  opacity: 0.9;
}

.leaf-top-left {
  top: 10px;
  left: 10px;
}

.leaf-bottom-left {
  bottom: 10px;
  left: 10px;
}

.picnic {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 250px;
  transform: rotate(-10deg);
}


h1 {
  color: #9b1c1c;
  margin-bottom: 20px;
  font-size: 32px;
}

.avatar {
  width: 100px;
  height: 100px;
  border: 4px solid #444;
  border-radius: 50%;
  margin: 0 auto 20px;
  background-image: url('https://cdn-icons-png.flaticon.com/512/847/847969.png');
  background-size: 50%;
  background-repeat: no-repeat;
  background-position: center;
}

.input {
  width: 100%;
  padding: 12px;
  margin-top: 14px;
  border-radius: 25px;
  border: 2px solid #555;
  outline: none;
  font-size: 15px;
}

.forgot {
  display: block;
  margin: 6px 0 14px;
  color: #666;
  font-size: 13px;
}

.login-btn {
  width: 100%;
  padding: 12px;
  border-radius: 30px;
  background: #22c55e;
  border: none;
  color: white;
  font-size: 18px;
  cursor: pointer;
  margin-bottom: 16px;
  box-shadow: 0 3px 6px rgba(0,0,0,0.2);
}

.login-btn:hover {
  background: #16a34a;
}

.register {
  font-size: 14px;
  color: #8b0000;
}

.register a {
  color: #8b0000;
  font-weight: bold;
  cursor: pointer;
}


@media (max-width: 900px) {
  .login-wrapper {
    flex-direction: column;
    height: auto;
  }

  .left-side,
  .right-side {
    flex: none;
    height: auto;
    padding: 30px 0;
  }

  .picnic {
    display: none;
  }
}

@media (max-width: 500px) {
  .logo {
    width: 220px;
  }

  .login-box {
    width: 90%;
  }

  .avatar {
    width: 70px;
    height: 70px;
  }
}*/
</style>
