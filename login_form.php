<form id="loginForm">
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Password</label>
    <input type="password" name="password" class="form-control" required>
  </div>

  <div id="login-message" class="text-danger mb-3"></div>

  <button type="submit" class="btn btn-primary w-100">Login</button>
  <div class="text-center mt-3">
    <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot Password?</a>
  </div>

  <hr class="my-4">

  <div class="text-center">
    <p>Don't have an account?</p>
    <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal" class="btn btn-outline-primary">
      Create Account
    </a>
  </div>
</form>