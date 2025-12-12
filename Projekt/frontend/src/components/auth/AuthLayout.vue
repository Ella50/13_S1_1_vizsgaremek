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
                <!-- Abrosz kép háttérként????? -->
            </div>
        </div>
    </div>

    <div class="right-side row-12">
      <!-- A form tartalma-->
      <slot></slot>
    </div>
  </div>
</template>

<script>
export default {
  name: "AuthLayout"
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
    bottom: -2%;
    left: -14%;
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

.login-form input, .login-form select {
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

    overflow: hidden; 
  }
  .left-side{
    width: 100%;
    height: 30%;
    overflow: hidden;
 }
  .right-side {
    width: 100%;
    height: 70%;
    overflow: hidden;
  }
 .upper, .middle, .bottom {
  height: 33%;
}

  .bottom-left img {
    max-width: 20%;
    position: absolute;
    bottom: -4%;
    left: -7%;
  }
  .upper img {
    max-width: 20%;
    position: absolute;
    top: -1%;
    left: -4%;
  }
}
  
</style>
