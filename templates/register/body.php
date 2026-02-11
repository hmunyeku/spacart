<!-- Register Page -->
<div class="spacart-auth-card card">
    <div class="card-content">
        <h4><i class="material-icons left">person_add</i>Inscription</h4>

        <form id="spacart-register-form">
            <div class="row" style="margin-bottom:0;">
                <div class="input-field col s6">
                    <input type="text" name="firstname" id="reg-firstname" required>
                    <label for="reg-firstname">Prénom *</label>
                </div>
                <div class="input-field col s6">
                    <input type="text" name="lastname" id="reg-lastname" required>
                    <label for="reg-lastname">Nom *</label>
                </div>
            </div>

            <div class="input-field">
                <i class="material-icons prefix">email</i>
                <input type="email" name="email" id="reg-email" required>
                <label for="reg-email">Email *</label>
            </div>

            <div class="input-field">
                <i class="material-icons prefix">phone</i>
                <input type="tel" name="phone" id="reg-phone">
                <label for="reg-phone">Téléphone</label>
            </div>

            <div class="input-field">
                <i class="material-icons prefix">business</i>
                <input type="text" name="company" id="reg-company">
                <label for="reg-company">Société (optionnel)</label>
            </div>

            <div class="input-field">
                <i class="material-icons prefix">lock</i>
                <input type="password" name="password" id="reg-password" required minlength="6">
                <label for="reg-password">Mot de passe * (min 6 car.)</label>
            </div>

            <div class="input-field">
                <i class="material-icons prefix">lock</i>
                <input type="password" name="password_confirm" id="reg-password-confirm" required>
                <label for="reg-password-confirm">Confirmer le mot de passe *</label>
            </div>

            <button type="submit" class="btn btn-large" style="width:100%;">
                <i class="material-icons left">person_add</i>Créer mon compte
            </button>
        </form>

        <div class="spacart-auth-divider">
            <span>ou</span>
        </div>

        <div class="center-align">
            <p>Déjà un compte ?</p>
            <a href="#/login" class="btn btn-flat spacart-spa-link">
                <i class="material-icons left">login</i>Se connecter
            </a>
        </div>
    </div>
</div>
