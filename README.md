# dmchurch — 동명교회 Rhymix 커스텀 소스

교회 홈페이지([dmchurch.kr](https://dmchurch.kr))의 **커스텀 모듈·레이아웃·애드온**만 관리하는 저장소입니다.  
전체 Rhymix 코어는 VPS Docker 이미지에 있으며, 이 저장소는 **수정·추가한 부분만** 담습니다.

## PC 작업 폴더

```
d:\교회사진\교회홈피자료\20260610랭크업백업\cursor_chat\rhymix_church_write\
```

## 배포 흐름

```
PC에서 소스 수정
  → syncup.bat        (GitHub push)
  → pulldeploy.bat    (VPS pull + 반영 + 캐시 삭제)
```

## 주요 배치 파일 (PC)

| 파일 | 설명 |
|------|------|
| `syncup.bat` | 변경분 Git commit + GitHub push |
| `pulldeploy.bat` | VPS SSH 한 번에 pull·배포 |
| `backupdown.bat` | VPS 백업 tar.gz → PC 다운로드 |

## VPS 경로

| 항목 | 경로 |
|------|------|
| Git clone | `/root/dmchurch-git` |
| 웹 루트 (호스트) | `/root/church-web/html` |
| Docker 컨테이너 | `church-rhymix` |
| 배포 스크립트 | `/root/church-web/scripts/vps_pull_deploy.sh` |

## VPS 한 줄 배포

```bash
bash /root/church-web/scripts/vps_pull_deploy.sh
```

최초 1회:

```bash
bash /root/dmchurch-git/scripts/vps_git_setup.sh
```

## GitHub

https://github.com/baesy69g-design/dmchurch
