<!-- Login Page -->
<div class="spacart-auth-card card">
    <div class="card-content">
        <h4><i class="material-icons left">login</i>Connexion</h4>

        <form id="spacart-login-form">
            <div class="input-field">
                <i class="material-icons prefix">email</i>
                <input type="email" name="email" id="login-email" required>
                <label for="login-email">Email</label>
            </div>

            <div class="input-field">
                <i class="material-icons prefix">lock</i>
                <input type="password" name="password" id="login-password" required>
                <label for="login-password">Mot de passe</label>
            </div>

            <p>
                <label>
                    <input type="checkbox" name="remember" class="filled-in">
                    <span>Se souvenir de moi</span>
                </label>
            </p>

            <button type="submit" class="btn btn-large" style="width:100%;">
                <i class="material-icons left">login</i>Se connecter
            </button>
        </form>

        <div class="spacart-auth-divider">
            <span>ou</span>
        </div>

        <div class="center-align">
            <p>Pas encore de compte ?</p>
            <a href="#/register" class="btn btn-flat spacart-spa-link">
                <i class="material-icons left">person_add</i>Créer un compte
            </a>
        </div>
    </div>
</div>
