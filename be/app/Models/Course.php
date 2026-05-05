<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * Model Course - Đại diện cho bảng courses trong database
 * Chứa thông tin khóa học chuẩn:
 * - id: khóa chính
 * - title: tên khóa học
 * - slug: URL thân thiện (duy nhất, tự động tạo từ title)
 * - description: mô tả khóa học
 * - thumbnail: ảnh đại diện
 * - level: trình độ (beginner, intermediate, advanced)
 * - price: giá khóa học (0 = miễn phí)
 * - status: trạng thái (draft, published)
 */
class Course extends Model
{
    use HasFactory;

    /**
     * Các trường có thể được gán giá trị hàng loạt (mass assignment)
     */
    protected $fillable = [
        'title',       // Tên khóa học
        'slug',        // URL thân thiện (sẽ tự tạo nếu để trống)
        'description', // Mô tả khóa học
        'thumbnail',   // Ảnh đại diện
        'level',       // Trình độ
        'price',       // Giá khóa học
        'status',      // Trạng thái (draft, published)
        'is_featured', // Khóa học nổi bật
    ];

    /**
     * Các kiểu dữ liệu được cast tự động
     */
    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * Các giá trị mặc định khi tạo mới
     */
    protected $attributes = [
        'price' => 0,
        'status' => 'draft',
    ];

    /**
     * Boot method - Tự động tạo slug khi tạo mới hoặc cập nhật title
     */
    protected static function boot()
    {
        parent::boot();

        // Tự động tạo slug từ title khi tạo mới (chạy trước khi insert vào DB)
        static::creating(function ($course) {
            if (empty($course->slug) && !empty($course->title)) {
                $baseSlug = Str::slug($course->title);
                $slug = $baseSlug;
                $counter = 1;
                
                // Kiểm tra slug đã tồn tại chưa, nếu có thì thêm suffix
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                
                $course->slug = $slug;
            }
        });

        // Tự động cập nhật slug khi title thay đổi
        static::updating(function ($course) {
            if ($course->isDirty('title') && !$course->isDirty('slug')) {
                $baseSlug = Str::slug($course->title);
                $slug = $baseSlug;
                $counter = 1;
                
                // Kiểm tra slug đã tồn tại chưa (trừ chính nó)
                while (static::where('slug', $slug)->where('id', '!=', $course->id)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                
                $course->slug = $slug;
            }
        });

        // NOTE: Auto-create chapters disabled to avoid duplicate chapters when seeding
        // If needed, uncomment the following block:
        //
        // static::created(function ($course) {
        //     $chapters = [
        //         ['title' => 'Từ vựng', 'type' => 'vocabulary', 'description' => 'Học từ vựng theo chủ đề'],
        //         ['title' => 'Ngữ pháp', 'type' => 'grammar', 'description' => 'Học cấu trúc và ngữ pháp'],
        //         ['title' => 'Nghe', 'type' => 'listening', 'description' => 'Luyện nghe qua audio'],
        //         ['title' => 'Nói', 'type' => 'speaking', 'description' => 'Luyện nói giao tiếp'],
        //     ];
        //
        //     foreach ($chapters as $index => $chapter) {
        //         $course->chapters()->create([
        //             'title' => $chapter['title'],
        //             'slug' => \Illuminate\Support\Str::slug($chapter['type']) . '-' . $course->id,
        //             'type' => $chapter['type'],
        //             'description' => $chapter['description'],
        //             'order' => $index + 1,
        //             'status' => 'published',
        //         ]);
        //     }
        //
        // });
    }

    /**
     * Định nghĩa mối quan hệ: Một Course có nhiều Chapters
     * Sắp xếp theo thứ tự order
     * Khi xóa Course, tất cả Chapters liên quan sẽ được xóa (cascade)
     *
     * @return HasMany
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('order');
    }

    /**
     * Định nghĩa mối quan hệ: Một Course có nhiều Lessons (qua Chapters)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function lessons()
    {
        return $this->hasManyThrough(Lesson::class, Chapter::class);
    }

    /**
     * Định nghĩa mối quan hệ: Một Course có nhiều UserCourse
     *
     * @return HasMany
     */
    public function userCourses(): HasMany
    {
        return $this->hasMany(UserCourse::class);
    }

    /**
     * Định nghĩa mối quan hệ: Một Course có 1 CourseQuiz (bài thi cuối khóa)
     *
     * @return HasOne
     */
    public function quiz()
    {
        return $this->hasOne(CourseQuiz::class);
    }

    /**
     * Accessor: Lấy giá hiển thị (format tiền VND)
     * 
     * @return string
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->price == 0) {
            return 'Miễn phí';
        }
        return number_format($this->price, 0, ',', '.') . ' đ';
    }

    /**
     * Accessor: Kiểm tra khóa học có miễn phí không
     * 
     * @return bool
     */
    public function getIsFreeAttribute(): bool
    {
        return $this->price == 0;
    }

    /**
     * Accessor: Kiểm tra khóa học đã publish chưa
     * 
     * @return bool
     */
    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Scope: Lọc khóa học đã publish
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope: Lọc khóa học theo trình độ
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $level
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
