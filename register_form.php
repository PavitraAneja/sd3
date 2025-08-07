<form id="registerForm">
  <div class="mb-3">
    <label class="form-label">First Name *</label>
    <input type="text" name="first_name" class="form-control" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Last Name *</label>
    <input type="text" name="last_name" class="form-control" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Phone Number</label>
    <input type="text" name="phone" class="form-control">
  </div>

  <div class="mb-3">
    <label class="form-label">Email *</label>
    <input type="email" name="email" class="form-control" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Password *</label>
    <input type="password" name="password" class="form-control" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Confirm Password *</label>
    <input type="password" name="confirm" class="form-control" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Budget Range</label>
    <input type="text" name="budget_range" class="form-control">
  </div>

  <div class="mb-3">
    <label class="form-label">Alert Frequency</label>
    <select name="alert_frequency" class="form-control">
      <option value="none">None</option>
      <option value="daily">Daily</option>
      <option value="weekly">Weekly</option>
    </select>
  </div>

  <div id="reg-message" class="text-danger mb-3"></div>

  <button type="submit" class="btn btn-primary w-100">Register</button>
</form>