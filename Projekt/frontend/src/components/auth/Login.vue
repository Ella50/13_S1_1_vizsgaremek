<template>
  <div class="login-wrapper col-12">
    <div class="left-side row-12">
        <div class="upper col-4">
            <img src="../../../falevel.png" alt="">
        </div>
        <div class="middle col-4">
            <img src="../../../eMenza.png" alt="">
        </div>
        <div class="bottom col-4">
            <div class="bottom-left">
                <img src="../../../faag.png" alt="">
            </div>
            <div class="bottom-right">
                
            </div>
        </div>
    </div>

    <div class="right-side row-12">
      <h2 class="title">Bejelentkezés</h2>

      <form @submit.prevent="handleLogin" class="login-form">
        <!--<div class="avatar"></div>-->

        <input type="email" v-model="form.email" placeholder="Email" required>
        <input type="password" v-model="form.password" placeholder="Jelszó" required>

        <a class="forgot" href="#">Elfelejtette a jelszavát?</a>

        <button type="submit" :disabled="loading">{{ loading ? 'Bejelentkezés...' : 'Bejelentkezés' }}</button>

        <p v-if="error" class="error">{{ error }}</p>
      </form>

      <p class="register-text">
        Még nincs fiókja? <router-link to="/register">Regisztráljon most</router-link>
      </p>
    </div>
  </div>
</template>




<script>
import axios from '../../axios'


export default {
    data() {
        return {
            form: {
                email: '',
                password: ''
            },
            loading: false,
            error: ''
        }
    },
    methods: {
        async handleLogin() {
            this.loading = true
            this.error = ''
            
            try {
                const response = await axios.post('/login', this.form)
                
                console.log('Login response:', response.data)
                
                const token = response.data.token || 
                            response.data.access_token || 
                            response.data.accessToken
                
                if (!token) {
                    console.error('No token in response:', response.data)
                    throw new Error('A szerver nem küldött érvényes tokent')
                }
                
                localStorage.setItem('auth_token', token)
                console.log('Token saved:', token.substring(0, 20) + '...')
                
                if (response.data.user) {
                    localStorage.setItem('user', JSON.stringify(response.data.user))
                } else {
                    localStorage.setItem('auth_data', JSON.stringify(response.data))
                }
                
                // Átirányítás dashboardra
                this.$router.push('/dashboard')
                
            } 

            catch (error) {
                console.error('Login error details:', {
                message: error.message,
                response: error.response?.data,
                status: error.response?.status
                })

                if (error.response?.status === 401) {
                  // Laravel alapértelmezett hibája
                  this.error = error.response?.data?.message || 'Hibás email vagy jelszó'
                } else if (error.response?.status === 422) {
                    // Validációs hiba
                    this.error = 'Érvénytelen adatok. Ellenőrizd a mezőket.'
                } else if (error.response?.status === 404) {
                    this.error = 'A bejelentkezési szolgáltatás nem elérhető'
                } else if (error.code === 'ERR_NETWORK') {
                    this.error = 'Hálózati hiba. Ellenőrizd az internetkapcsolatot.'
                } else if (error.message.includes('timeout')) {
                    this.error = 'Időtúllépés. Kérjük, próbáld újra.'
                } else {
                    this.error = error.response?.data?.message || 
                                error.message || 
                                'Ismeretlen bejelentkezési hiba'
                }
                
            } 

            finally {
                this.loading = false
            }
        }
    }
}
</script>

<style>


.upper, .middle, .bottom {
  height: 33%;
}

.middle{
    display: flex;
    justify-content: center;
    align-items: center;
    margin: auto;
}

.bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.upper img {
  max-width: 50%;
  height: auto;
  position: absolute;
  top: 0%;
  left: -10%;
}

.middle img {
  max-width: 200%;
  height: auto;
  float: center;
}

.bottom img {
  max-width: 100%;
  height: auto;
}

.bottom-left img {
    max-width: 40%;
    position: absolute;
    bottom: 0%;
    left: -15%;
}

.bottom-right{
    background-image: url("../../../abrosz.png");
    height: 100%;
    width: 100%;
    background-repeat: no-repeat;
    background-position: top left;


}

.login-wrapper {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  height: 100vh;
  width: 100%;
  overflow: hidden;
}



.left-side {
  width: 50%;
  height: 100%;
  background: #fff7e6;
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 2rem 0 0 2rem;
}


.right-side {
  width: 50%;
  background: #ffd294;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 3rem;
}

.title {
  font-size: 2.5rem;
  color: #9d1f1f;
  margin-bottom: 1.5rem;
}

.avatar {
  width: 110px;
  height: 110px;
  border-radius: 50%;
  background: #d1d1d1;
  margin: 0 auto 1.5rem auto;
}

.login-form {
  width: 70%;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.login-form input {
  width: 100%;
  padding: 0.8rem;
  border: 2px solid #555;
  border-radius: 25px;
  font-size: 1rem;
}

button {
  width: 100%;
  padding: 0.9rem;
  background: #1fa317;
  color: #fff;
  font-size: 1.2rem;
  font-weight: bold;
  border: none;
  border-radius: 25px;
  cursor: pointer;
}

button:disabled {
  background: #8f8f8f;
  cursor: not-allowed;
}

.forgot {
  font-size: 0.8rem;
  color: #555;
  text-align: center;
  margin-bottom: 1rem;
  text-decoration: none;
}

.register-text {
  margin-top: 1rem;
  font-size: 1rem;
  color: #8a1212;
}

.error {
  color: #c70000;
  text-align: center;
  margin-top: 0.5rem;
}

@media (max-width: 768px) {
  .login-wrapper {
    flex-direction: column;
    width: 100%;
    height: 100%;
    overflow: hidden; 
  }
  .left-side, .right-side {
    width: 100%;
    height: 50%;
    overflow: hidden;
 }
 .upper, .middle, .bottom {
  height: 33%;
}
}
</style>