"""HTTP regression checks against a disposable database with a CLI-created admin."""
import http.cookiejar
import os
import sys
import urllib.error
import urllib.parse
import urllib.request
from html.parser import HTMLParser


class Form(HTMLParser):
    def __init__(self, body):
        super().__init__(convert_charrefs=True)
        self.inputs = {}
        self.labels = set()
        self.feed(body)

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'input':
            self.inputs[attrs.get('name', '')] = attrs
        elif tag == 'label':
            self.labels.add(attrs.get('for'))


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


class Browser:
    def __init__(self, base):
        self.base = base.rstrip('/')
        self.cookies = http.cookiejar.CookieJar()
        self.client = urllib.request.build_opener(
            urllib.request.ProxyHandler({}),
            urllib.request.HTTPCookieProcessor(self.cookies), NoRedirect())

    def request(self, path, fields=None):
        data = urllib.parse.urlencode(fields).encode() if fields is not None else None
        req = urllib.request.Request(self.base + path, data=data)
        try:
            response = self.client.open(req, timeout=15)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            return response.status, response.read().decode(), response.headers

    def form(self, path='/admin/login.php'):
        status, body, _ = self.request(path)
        assert status == 200, f'Login form returned HTTP {status}'
        return Form(body), body


def run(base, username, email, password):
    browser = Browser(base)
    form, body = browser.form()
    assert form.inputs['username']['type'] == 'text', 'Username input rejects non-email usernames'
    assert form.inputs['password']['type'] == 'password', 'Password starts unmasked'
    for name in ['username', 'password']:
        assert form.inputs[name]['id'] in form.labels, 'An input has no linked label'
    assert 'adminPasswordToggle' in body and 'admin-login.js' in body
    assert 'remember' not in form.inputs, 'Unsupported persistence control was displayed'

    for path in ['/admin/login.php', '/admin/Login1.php']:
        form, body = Browser(base).form(path)
        assert 'Admin Login | Vueports' in body and 'Client Portal</h2>' not in body
        assert 'username' in form.inputs and 'csrf_token' in form.inputs

    status, body, _ = browser.request('/admin/login.php', {
        'csrf_token': 'invalid', 'username': username, 'password': password})
    assert status == 403 and 'Your session expired' in body

    form, _ = browser.form()
    escaped_identity = '<script>alert(1)</script>@example.invalid'
    status, body, _ = browser.request('/admin/login.php', {
        'csrf_token': form.inputs['csrf_token']['value'],
        'username': escaped_identity, 'password': 'invalid-test-password'})
    failed = Form(body)
    assert status == 200 and 'Invalid username, email, or password.' in body
    assert failed.inputs['username']['value'] == escaped_identity, 'Identifier was not retained'
    assert '<script>alert(1)</script>' not in body, 'Identifier was rendered as executable markup'
    assert failed.inputs['password'].get('value', '') == '', 'Password was echoed back'
    assert failed.inputs['username']['aria-invalid'] == 'true'
    assert failed.inputs['password']['aria-invalid'] == 'true'

    for fields in [{'username[]': 'invalid', 'password': password},
                   {'username': username, 'password[]': 'invalid'}]:
        form, _ = browser.form()
        status, body, _ = browser.request('/admin/login.php', {
            'csrf_token': form.inputs['csrf_token']['value'], **fields})
        assert status == 200 and 'Please enter your username or email and password.' in body

    for path, key, identity in [('/admin/login.php', 'username', username.upper()),
                                ('/admin/login.php', 'username', email.upper()),
                                ('/admin/Login1.php', 'email', email)]:
        signed_in = Browser(base)
        form, _ = signed_in.form(path)
        before = next(c.value for c in signed_in.cookies if c.name == 'VUEPORTSSESSID')
        status, body, headers = signed_in.request(path, {
            'csrf_token': form.inputs['csrf_token']['value'], key: identity, 'password': password})
        assert status == 302 and headers.get('Location', '').endswith('dashboard.php'), 'Administrator sign-in failed'
        after = next(c.value for c in signed_in.cookies if c.name == 'VUEPORTSSESSID')
        assert before != after, 'Session ID was not rotated at sign-in'
        assert signed_in.request('/admin/dashboard.php')[0] == 200, 'Administrator session did not authorize dashboard'

    print('PASS admin username/email inputs, legacy route, CSRF, validation, escaping, password handling and authenticated sessions')


if __name__ == '__main__':
    run(sys.argv[1], os.environ['VUEPORTS_ADMIN_TEST_USERNAME'],
        os.environ['VUEPORTS_ADMIN_TEST_EMAIL'], os.environ['VUEPORTS_ADMIN_TEST_PASSWORD'])
