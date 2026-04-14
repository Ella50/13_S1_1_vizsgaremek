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

    <div class="right-side">
      <div class="right-side-content">
        <!-- A form tartalma -->
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "AuthLayout"
}
</script>

<style>

/*
.password-wrapper {
  position: relative;
  width: 100%;
}

.password-wrapper input {
  width: 100%;
  padding: 0.8rem;
  padding-right: 45px; 
  border: 2px solid #555;
  border-radius: 25px;
  font-size: 1rem;
  box-sizing: border-box; 
}


.password-wrapper input[type="password"]::-ms-reveal,
.password-wrapper input[type="password"]::-ms-clear,
.password-wrapper input[type="password"]::-webkit-contacts-auto-fill-button,
.password-wrapper input[type="password"]::-webkit-credentials-auto-fill-button {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  pointer-events: none !important;
  width: 0 !important;
  height: 0 !important;
}

.password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  margin: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  color: #666;
  opacity: 0.7;
  transition: all 0.2s;
  outline: none;
}

.password-toggle:hover {
  opacity: 1;
  color: #333;
}

.password-toggle:focus {
  outline: none;
}

.toggle-icon {
  width: 20px;
  height: 20px;
  stroke: currentColor;
  fill: none;
  transition: all 0.2s;
  display: block;
  flex-shrink: 0;
}


input[type="password"]::-ms-reveal {
  display: none;
}
  */

  

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

.register-text a:hover {
  color: #e94f4f;
  text-decoration: underline;
}


.left-side {
  width: 50%;
  height: 100vh;
  background: #fff7e6;
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  overflow: hidden; 
  flex-shrink: 0; 
}

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
    bottom: -1%;
    left: -14%;
}

.bottom-right{
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



.right-side {
  width: 50%;
  height: 100vh;
  background: #ffd294;
  overflow-y: auto; 
  overflow-x: hidden; 
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2rem 1rem;
  flex-shrink: 0; 
}

.right-side-content {
  width: 100%;
  max-width: 800px;
  margin: auto; 
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2rem;
}

@media (max-width: 768px) {
  .right-side {
    padding: 1rem 0.5rem;
  }
  
  .right-side-content {
    padding: 1.5rem;
  }
}

@media (max-width: 480px) {
  .right-side-content {
    padding: 1rem;
  }
}
.title {
  font-size: 2.5rem;
  color: #9d1f1f;
  margin-bottom: 1.5rem;
}



.login-form {
  width: 70%;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.login-form input, .login-form select {
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
}

.btn_auth:disabled {
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

@media (max-width: 1024px) {
  .right-side {
    padding: 1.5rem 1rem;
  }
}

@media (max-width: 768px) {
  .login-wrapper {
    flex-direction: column;
    height: 100vh;
    overflow: hidden;
  }

  .left-side {
    width: 100%;
    min-height: 35vh;
    height: auto;
    overflow: hidden;
  }

  .upper, .middle, .bottom {
    height: 33.33%;
  }

  .upper img {
    max-width: 25%;
    left: -5%;
  }

  .bottom-left img {
    max-width: 25%;
    left: -7%;
  }

  .middle img {
    max-width: 150%;
  }

  .right-side {
    width: 100%;
    flex: 1; 
    overflow-y: auto;
    overflow-x: hidden;
    padding: 1.5rem 1rem;
    min-height: 0;
  }

  .right-side-content {
    padding-bottom: 2rem;
  }
}

@media (max-width: 480px) {
  .left-side {
    height: 25vh; 
    min-height: 150px;
  }

  .right-side {
    height: 75vh;
    padding: 1rem 0.8rem;
  }

  .upper img {
    max-width: 25%;
  }

  .bottom-left img {
    max-width: 25%;
  }

  .middle img {
    max-width: 120%;
  }
}

@media (max-width: 360px) {
  .left-side {
    height: 20vh;
    min-height: 120px;
  }

  .right-side {
    height: 80vh;
  }
}


@media (min-height: 800px) and (max-width: 768px) {
  .left-side {
    height: 25vh;
  }
  
  .right-side {
    height: 75vh;
  }
}


@media (orientation: landscape) and (max-width: 1024px) {
  .left-side {
    height: 100vh;
    width: 40%;
  }
  
  .right-side {
    height: 100vh;
    width: 60%;
    overflow-y: auto;
  }
}
  
</style>
