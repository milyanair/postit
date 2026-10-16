# App store upload checklist (needs J's approval at each public step)

1. **GitHub repo:** create `postit` (public, AGPL-3.0-or-later). Replace `OWNER` in appinfo/info.xml (website, bugs, repository, screenshots). Push the source and add screenshots/note.png and screenshots/menu.png.
2. **Certificate signing request:**
   ```
   mkdir -p ~/.nextcloud/certificates && cd ~/.nextcloud/certificates
   openssl req -nodes -newkey rsa:4096 -keyout postit.key -out postit.csr -subj "/CN=postit"
   ```
   Keep postit.key private. Open a pull request with postit.csr at https://github.com/nextcloud/app-certificate-requests. After it is merged, save the issued certificate as `~/.nextcloud/certificates/postit.crt`.
3. **App store account:** sign in at https://apps.nextcloud.com (GitHub login). Use "Register app" with postit.crt and the signature of the string `postit`:
   `echo -n "postit" | openssl dgst -sha512 -sign postit.key | openssl base64 -A`
4. **Code signature (optional but recommended):** `occ integrity:sign-app --path=/path/to/postit --privateKey=postit.key --certificate=postit.crt` (creates appinfo/signature.json).
5. **Build:** `make sign` creates build/postit-<version>.tar.gz and its .sig.
6. **Release:** create a GitHub release `v<version>` and attach the tarball. The release URL looks like `https://github.com/OWNER/postit/releases/download/v1.0.0/postit-1.0.0.tar.gz`.
7. **Upload:** at https://apps.nextcloud.com/developer/apps/releases/new, enter the release URL and paste the .sig contents. Check the listing and the screenshots.
