#!/usr/bin/env python3
"""Import legacy RankUp main slides, hero images, and 8 tile images into Rhymix church config."""
import json
import os
import re
import shutil
from pathlib import Path

BASE = Path(__file__).resolve().parent
HTML = BASE
BACKUP = Path(os.environ.get('RANKUP_BACKUP', '/root/church-web/rankup_backup'))
TILE_DIR = HTML / 'files' / 'church' / 'main_tile'
SLIDE_DIR = HTML / 'files' / 'church' / 'main_slide'
HERO_DIR = HTML / 'files' / 'church' / 'main_hero'
LOGO_SRC = BACKUP / 'RAD' / 'PEG' / 'logo_15276696610078.jpg'
LOGO_DEST = HTML / 'files' / 'church' / 'logo.jpg'
BG_BODY_SRC = BACKUP / 'design' / 'site' / 'bg_2.png'
BG_BODY_DEST = HTML / 'files' / 'church' / 'bg_body.png'

# rankup_banner_list.no => first image real_name (from db260610.sql)
BANNER_IMAGE_FILES = {
    '1': 'f4897b693dd16e9e8832222a536ad77b42efc4041753075912.jpg',   # maint1 worship
    '10': '4a675b65949147714656503b53854919f29521411500983749.jpg',  # maint2 event
    '11': '8690f24a1d98350f4841177ce099c88f2fa9da681592803028.jpg',  # maint3 school
    '12': 'ac2f777b16a43affbf6d902a5acfb5d40fa760061500864697.jpg',  # maint4 schedule
    '13': '192a724b43cf0b6510983f1b2f377ee420d5f83d1500865158.jpg',  # maint5 jubo
    '14': 'fa138dd4ceede48ff09ae84349160d653a7c2b3d1500865263.jpg',  # maint6 newface
    '16': 'e09fd68ba97556bb52d9d46207d14622d05027ef1500983762.jpg',  # main2 rice
    '18': '0ed8fb5a0e4439c0b223cb7d685c9aa319c5bd2a1500865242.jpg',  # main4 scholarship
}

# list_no => banner id string
BANNER_IDS = {
    '1': 'maint1',
    '10': 'maint2',
    '11': 'maint3',
    '12': 'maint4',
    '13': 'maint5',
    '14': 'maint6',
    '16': 'main2',
    '18': 'main4',
    '19': 'banner_banner01',
    '21': 'banner_banner02',
}

# banner id => our tile key (8 grid tiles)
TILE_BANNERS = {
    'maint1': 'worship_time',
    'maint2': 'event_photo',
    'maint3': 'church_school',
    'maint4': 'pastoral_schedule',
    'maint5': 'weekly_bulletin',
    'maint6': 'new_family',
    'main2': 'rice_share',
    'main4': 'scholarship',
}

TILE_LIST_NOS = {v: k for k, v in BANNER_IDS.items() if v in TILE_BANNERS}

MAIN_SLIDES = [
    'imglk.15646301678352.jpg',
    'imglk.15646301678356.jpg',
    'imglk.15646301678360.jpg',
    'imglk.15646301678378.jpg',
]

QUICK_LINK_FILES = [
    ('1e6a624a89c837778e0c8868673b5ee70afb16341499927854.png', 'quick_1.png'),
    ('69640b2cae05e2bdbcae970722de6904695d95b41499927859.png', 'quick_2.png'),
    ('41bb782fec5fcb0c1faff30fc57322a0b894bea21499927862.png', 'quick_3.png'),
    ('3f61db3c443cb99753d9dbe58b8942b074a772741499927865.png', 'quick_4.png'),
]

PASTOR_SRC = ('21', '77771fe572ca5538d3d7d8ed1ad64d48877afee01499928509.png', 'pastor.png')


def banner_image_path(list_no: str, real_name: str) -> Path | None:
    p = BACKUP / 'PEG' / 'banner' / list_no / real_name
    return p if p.is_file() else None


def parse_banner_images(sql_text: str) -> dict[str, list[dict]]:
    """Return banner_id => [{real_name, link, sort}, ...]."""
    out: dict[str, list[dict]] = {}
    for m in re.finditer(
        r"\((\d+),'(\d+)',(\d+),'attach','a:2:\{s:11:\"origin_name\";s:\d+:\"[^\"]*\";s:9:\"real_name\";s:\d+:\"([^\"]+)\";[^)]*'([^']*)'",
        sql_text,
    ):
        list_no = m.group(2)
        sort = int(m.group(3))
        real_name = m.group(4)
        link = m.group(5)
        banner_id = BANNER_IDS.get(list_no)
        if not banner_id:
            continue
        out.setdefault(banner_id, []).append({'real_name': real_name, 'link': link, 'sort': sort})
    for items in out.values():
        items.sort(key=lambda x: x['sort'])
    return out


def load_sql_banner_images() -> dict[str, list[dict]]:
    sql_path = BACKUP / 'db260610.sql'
    if not sql_path.is_file():
        return {}
    text = sql_path.read_text(errors='ignore')
    idx = text.find('INSERT INTO `rankup_banner_image`')
    if idx < 0:
        return {}
    chunk = text[idx: idx + 120000]
    return parse_banner_images(chunk)


def copy_file(src: Path, dest: Path) -> bool:
    if not src.is_file():
        return False
    dest.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(src, dest)
    os.chmod(dest, 0o644)
    return True


def rel_url(path: Path) -> str:
    rel = path.relative_to(HTML).as_posix()
    return './' + rel


def main() -> None:
    overwrite = '--overwrite' in os.sys.argv
    TILE_DIR.mkdir(parents=True, exist_ok=True)
    SLIDE_DIR.mkdir(parents=True, exist_ok=True)
    HERO_DIR.mkdir(parents=True, exist_ok=True)

    banner_images = load_sql_banner_images()
    tiles: dict[str, dict] = {}
    hero: dict[str, str] = {}

    for banner_id, tile_key in TILE_BANNERS.items():
        list_no = TILE_LIST_NOS.get(banner_id)
        if not list_no:
            continue
        real_name = BANNER_IMAGE_FILES.get(list_no)
        if not real_name:
            items = banner_images.get(banner_id, [])
            real_name = items[0]['real_name'] if items else None
        if not real_name:
            continue
        src = banner_image_path(list_no, real_name)
        if not src:
            continue
        dest = TILE_DIR / f'{tile_key}.jpg'
        if dest.exists() and not overwrite:
            tiles[tile_key] = {'image_url': rel_url(dest), 'link_url': ''}
            print('tile exists', tile_key)
            continue
        if copy_file(src, dest):
            tiles[tile_key] = {'image_url': rel_url(dest), 'link_url': ''}
            print('tile', tile_key, '<-', src.name)

    slides: list[str] = []
    slide_src_dir = BACKUP / 'design' / 'main'
    for i, name in enumerate(MAIN_SLIDES, 1):
        src = slide_src_dir / name
        dest = SLIDE_DIR / f'slide{i}.jpg'
        if dest.exists() and not overwrite:
            slides.append(rel_url(dest))
            continue
        if copy_file(src, dest):
            slides.append(rel_url(dest))
            print('slide', i, '<-', name)

    for src_name, dest_name in QUICK_LINK_FILES:
        src = banner_image_path('19', src_name)
        dest = HERO_DIR / dest_name
        if src and (not dest.exists() or overwrite):
            if copy_file(src, dest):
                hero[dest_name] = rel_url(dest)
                print('quick', dest_name)

    list_no, src_name, dest_name = PASTOR_SRC
    src = banner_image_path(list_no, src_name)
    dest = HERO_DIR / dest_name
    if src and (not dest.exists() or overwrite):
        if copy_file(src, dest):
            hero['pastor'] = rel_url(dest)
            print('pastor', dest_name)

    if hero:
        print('hero files copied:', len(hero))

    if LOGO_SRC.is_file() and (not LOGO_DEST.exists() or overwrite):
        if copy_file(LOGO_SRC, LOGO_DEST):
            print('logo', LOGO_DEST.name, '<-', LOGO_SRC.name)

    if BG_BODY_SRC.is_file() and (not BG_BODY_DEST.exists() or overwrite):
        if copy_file(BG_BODY_SRC, BG_BODY_DEST):
            print('bg_body', BG_BODY_DEST.name, '<-', BG_BODY_SRC.name)

    print('files done tiles=', len(tiles), 'slides=', len(slides), 'hero=', len(hero))
    print('run: docker exec church-rhymix php .../scripts/import_main_tiles_config.php')


if __name__ == '__main__':
    main()
